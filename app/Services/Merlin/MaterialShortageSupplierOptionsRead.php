<?php

namespace App\Services\Merlin;

use App\Enums\MaterialRequirementSourceValidity;
use App\Enums\ProblemCaseEvaluationResult;
use App\Enums\ProblemCaseLifecycle;
use App\Enums\ProblemCaseType;
use App\Repositories\Contracts\ProblemCaseRepositoryInterface;
use App\Services\Admin\MaterialShortageProblemCaseResolver;
use App\Services\Admin\SupplierOptionService;
use App\Support\Merlin\SupplierOptionsObservation;
use App\Support\Procurement\ProcurementDecimal;
use App\Support\Procurement\ProcurementRequirementInput;
use App\Support\Procurement\ProcurementRequirementProvenance;
use App\Support\Procurement\SupplierOptionEvaluationException;
use App\Support\Procurement\SupplierOptionQuery;
use App\Support\Procurement\SupplierOptionReadSnapshot;
use Illuminate\Support\Facades\DB;
use PDO;

/** Concrete trusted composition; this is not an arbitrary transaction or query bypass. */
final class MaterialShortageSupplierOptionsRead
{
    private ?SupplierOptionQuery $query = null;

    private ?PDO $snapshotPdo = null;

    public function __construct(
        private readonly ProblemCaseRepositoryInterface $cases,
        private readonly MaterialShortageProblemCaseResolver $resolver,
        private readonly SupplierOptionService $suppliers,
        private readonly SupplierOptionReadSnapshot $snapshot,
    ) {}

    public function observe(string $problemCaseId): SupplierOptionsObservation
    {
        if ($this->snapshotPdo !== null) {
            throw new SupplierOptionEvaluationException('SUPPLIER_OPTION_MATERIAL_SHORTAGE_SCOPE_ACTIVE');
        }

        // The existing snapshot helper owns policy setup, caller rejection and no retry.
        return $this->snapshot->evaluate(function () use ($problemCaseId): SupplierOptionsObservation {
            $this->snapshotPdo = DB::connection()->getPdo();
            try {
                $case = $this->cases->findForCurrentEvaluation($problemCaseId);
                if ($case->lifecycle !== ProblemCaseLifecycle::Open || ! in_array($case->type, [ProblemCaseType::MaterialShortage], true)) {
                    return new SupplierOptionsObservation('CASE_NOT_CURRENT');
                }
                $evaluation = $this->resolver->resolve($problemCaseId);
                if ($evaluation->invalidationCandidate()) {
                    return new SupplierOptionsObservation('SOURCE_INVALID');
                }
                if ($evaluation->sourceValidity !== MaterialRequirementSourceValidity::Valid
                    || ! $evaluation->sourceValidityAuthoritative || ! $evaluation->nettingAuthoritative()) {
                    return new SupplierOptionsObservation('CURRENT_STATE_UNDETERMINED');
                }
                if ($evaluation->evaluation === ProblemCaseEvaluationResult::Resolved) {
                    return new SupplierOptionsObservation('NO_CURRENT_SHORTAGE');
                }
                $netting = $evaluation->netting;
                if ($evaluation->evaluation !== ProblemCaseEvaluationResult::Active || $netting === null
                    || $evaluation->problemCaseId !== $case->id
                    || $evaluation->materialRequirementId !== $case->material_requirement_id
                    || $netting->requirementId !== $case->material_requirement_id
                    || ProcurementDecimal::toScaledInteger($netting->netRequirement, 3, 'net_requirement') <= 0) {
                    return new SupplierOptionsObservation('CURRENT_STATE_UNDETERMINED');
                }
                $observed = now(config('app.timezone'));
                $this->query = new SupplierOptionQuery(new ProcurementRequirementInput(
                    $netting->requiredItemId, $netting->netRequirement, $netting->requiredAt, $netting->unit,
                    $observed->toDateString(),
                    new ProcurementRequirementProvenance(
                        'material_requirement_netting', $netting->requirementId, 'net_requirement',
                        $observed->toIso8601String(), 'all_requirements',
                    ),
                ));

                $result = $this->suppliers->evaluateMaterialShortageObservation($this);

                return new SupplierOptionsObservation('ACTIVE', $observed->toIso8601String(), $result);
            } finally {
                try {
                    // Check every outcome, including early domain exits, for snapshot loss.
                    $this->assertSnapshotActive();
                } finally {
                    // No authority survives this observation, including failed reads.
                    $this->query = null;
                    $this->snapshotPdo = null;
                }
            }
        });
    }

    /** @internal SupplierOptionService only; callers cannot supply a query or establish this scope. */
    public function supplierQuery(): SupplierOptionQuery
    {
        if ($this->query === null) {
            throw new SupplierOptionEvaluationException('SUPPLIER_OPTION_MATERIAL_SHORTAGE_SCOPE_REQUIRED');
        }
        $this->assertSnapshotActive();

        return $this->query;
    }

    private function assertSnapshotActive(): void
    {
        $connection = DB::connection();
        if ($this->snapshotPdo === null
            || $connection->getPdo() !== $this->snapshotPdo
            || $connection->transactionLevel() < 1 || ! $this->snapshotPdo->inTransaction()) {
            throw new SupplierOptionEvaluationException('SUPPLIER_OPTION_MATERIAL_SHORTAGE_SCOPE_REQUIRED');
        }

    }
}
