<?php

namespace App\Services\Merlin;

use App\Enums\MaterialRequirementSourceValidity;
use App\Enums\MerlinAdmissionOutcome as Outcome;
use App\Enums\ProblemCaseEvaluationResult;
use App\Enums\ProblemCaseLifecycle;
use App\Enums\ProblemCaseType;
use App\Models\MerlinRun;
use App\Models\ProblemCase;
use App\Models\User;
use App\Repositories\Contracts\MerlinRunRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\Merlin\MerlinAdmissionContext;
use App\Support\Merlin\MerlinAdmissionInfrastructureException;
use App\Support\Merlin\MerlinAdmissionResult;
use App\Support\Merlin\MerlinAdmissionTransaction;
use App\Support\Merlin\MerlinAdmissionUniqueCollision;
use App\Support\Merlin\MerlinRunConfiguration;
use App\Support\Merlin\StartInvestigationRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Throwable;

/** Internal admission only: the Laravel guard supplies the actor; no execution is dispatched. */
final class StartMerlinInvestigationService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly MerlinAdmissionTransaction $transaction,
        private readonly MerlinRunRepositoryInterface $runs,
        private readonly MerlinAdmissionAuditWriterInterface $audit,
        private readonly MerlinRunConfiguration $configuration,
    ) {}

    public function start(StartInvestigationRequest $request): MerlinAdmissionResult
    {
        $principal = Auth::user();
        if (! $principal instanceof User || ! $this->authorized($this->users->findForSupplierOptionsTool($principal->id))) {
            return new MerlinAdmissionResult(Outcome::Denied);
        }
        try {
            return $this->attempt($request, $principal->id, false);
        } catch (QueryException $exception) {
            if (! MerlinAdmissionUniqueCollision::matches($exception)) {
                throw $exception;
            }
            // One new read-only reconciliation, never another automatic admission/write attempt.
            if (! $this->samePrincipal($principal->id) || ! $this->authorized($this->users->findForSupplierOptionsTool($principal->id))) {
                return new MerlinAdmissionResult(Outcome::Denied);
            }

            return $this->attempt($request, $principal->id, true);
        }
    }

    private function attempt(StartInvestigationRequest $request, int $actorId, bool $reconcile): MerlinAdmissionResult
    {
        $callbackFinished = false;
        $callbackEntered = false;
        try {
            return $this->transaction->run($request->problemCaseId, function (MerlinAdmissionContext $context) use ($request, $actorId, $reconcile, &$callbackFinished, &$callbackEntered): MerlinAdmissionResult {
                $callbackEntered = true;
                $result = $this->admit($context, $request, $actorId, $reconcile);
                $callbackFinished = true;

                return $result;
            });
        } catch (Throwable $exception) {
            if ($callbackFinished) {
                // Commit/cleanup acknowledgement failed: a later authorized call must resolve identity.
                throw new MerlinAdmissionInfrastructureException('MERLIN_ADMISSION_COMMIT_UNCERTAIN', previous: $exception);
            }
            if (! $callbackEntered && $exception instanceof ModelNotFoundException && $exception->getModel() === ProblemCase::class) {
                return new MerlinAdmissionResult($this->samePrincipal($actorId) && $this->authorized($this->users->findForSupplierOptionsTool($actorId))
                    ? Outcome::CaseNotEligible : Outcome::Denied);
            }

            throw $exception;
        }
    }

    private function admit(MerlinAdmissionContext $context, StartInvestigationRequest $request, int $actorId, bool $reconcile): MerlinAdmissionResult
    {
        if (! $this->samePrincipal($actorId) || ! $this->authorized($context->freshActor($actorId))) {
            return new MerlinAdmissionResult(Outcome::Denied);
        }
        $connection = $context->connectionName();
        $existing = $this->runs->findIdentity($connection, $actorId, $request);
        if ($existing !== null) {
            return $this->replay($existing, $actorId, $request);
        }
        if ($this->runs->hasActive($connection, $request->problemCaseId)) {
            return new MerlinAdmissionResult(Outcome::ActiveRunExists);
        }
        if ($reconcile) {
            throw new MerlinAdmissionInfrastructureException('MERLIN_ADMISSION_COLLISION_UNRESOLVED');
        }
        $case = $context->case();
        if ($case->getRawOriginal('type') !== ProblemCaseType::MaterialShortage->value || $case->lifecycle !== ProblemCaseLifecycle::Open) {
            return new MerlinAdmissionResult(Outcome::CaseNotEligible);
        }
        $observedAt = Carbon::now('UTC');
        $evaluation = $context->resolveCurrent();
        $netRequirement = $evaluation->netRequirement();
        if ($evaluation->problemCaseId !== $case->id || $evaluation->materialRequirementId !== $case->material_requirement_id
            || $evaluation->netting?->requirementId !== $case->material_requirement_id
            || $evaluation->sourceValidity !== MaterialRequirementSourceValidity::Valid
            || ! $evaluation->sourceValidityAuthoritative || ! $evaluation->nettingAuthoritative()
            || $evaluation->invalidationCandidate() || $evaluation->evaluation !== ProblemCaseEvaluationResult::Active
            || $netRequirement === null || ! preg_match('/^\d+\.\d{3}$/D', $netRequirement)
            || trim(str_replace('.', '', $netRequirement), '0') === '') {
            return new MerlinAdmissionResult(Outcome::CaseNotEligible);
        }
        $history = $this->runs->appendStarting($connection, $evaluation, $observedAt);
        $run = $this->runs->createPending($connection, $actorId, $request, $history->id, $this->configuration, $observedAt);
        $this->audit->write($connection, $run, $observedAt);

        return new MerlinAdmissionResult(Outcome::Created, $run->id);
    }

    private function replay(MerlinRun $run, int $actorId, StartInvestigationRequest $request): MerlinAdmissionResult
    {
        $attributes = $run->getAttributes();
        $expected = $request->canonicalRequest();
        // V1 has only the case in its payload: a valid identical tuple cannot encode a different V1 request.
        if ($run->historical_actor_id !== $actorId || $run->initiating_user_id !== $actorId
            || $run->problem_case_id !== $request->problemCaseId || ($attributes['idempotency_key'] ?? null) !== $request->idempotencyKey
            || (int) ($attributes['request_schema_version'] ?? 0) !== 1 || (int) ($attributes['fingerprint_version'] ?? 0) !== 1
            || $run->canonical_request !== $expected
            || ! hash_equals(hash('sha256', $run->canonical_request), (string) ($attributes['request_fingerprint'] ?? ''))) {
            throw new MerlinAdmissionInfrastructureException('MERLIN_ADMISSION_STORED_IDENTITY_INVALID_OR_UNSUPPORTED');
        }

        return new MerlinAdmissionResult(Outcome::Replayed, $run->id);
    }

    /** @phpstan-impure Gate decisions can change between transaction attempts. */
    private function authorized(?User $actor): bool
    {
        return $actor !== null && Gate::forUser($actor)->allows('inventory.view') && Gate::forUser($actor)->allows('item-suppliers.view');
    }

    private function samePrincipal(int $actorId): bool
    {
        $principal = Auth::user();

        return $principal instanceof User && $principal->id === $actorId;
    }
}
