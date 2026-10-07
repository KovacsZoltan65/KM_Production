<?php

namespace App\Services\Merlin;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AuditLogService;
use App\Support\Merlin\SupplierOptionsToolContext;
use App\Support\Merlin\SupplierOptionsToolProjection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Throwable;

/** Internal backend boundary; no route, AI provider, dynamic dispatch or business writes. */
final class GetSupplierOptionsTool
{
    public const NAME = 'get_supplier_options';

    public const VERSION = '1';

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly MaterialShortageSupplierOptionsRead $read,
        private readonly SupplierOptionsToolProjection $projection,
        private readonly AuditLogService $audit,
    ) {}

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => self::NAME,
            'category' => 'READ',
            'description' => 'Read supplier source options for the bound, authorized, currently active material shortage case. No supplier selection or procurement execution.',
            'input_schema' => [
                'type' => 'object',
                'properties' => ['problem_case_id' => ['type' => 'string', 'format' => 'uuid']],
                'required' => ['problem_case_id'],
                'additionalProperties' => false,
            ],
        ];
    }

    /** Availability is disclosure authorization only; execution never consumes this result. @return array{available: bool, code: string} */
    public function availability(SupplierOptionsToolContext $context, mixed $input, string $toolName = self::NAME): array
    {
        [$code] = $this->authorize($context, $input, $toolName);

        return ['available' => $code === 'AUTHORIZED', 'code' => $code];
    }

    /** @return array<string, mixed> JSON primitives only. */
    public function execute(SupplierOptionsToolContext $context, mixed $input, string $toolName = self::NAME): array
    {
        // Never attempt audit inside a caller-owned transaction or end that transaction.
        $connection = DB::connection();
        $pdo = $connection->getRawPdo();
        if ($connection->transactionLevel() !== 0 || ($pdo instanceof PDO && $pdo->inTransaction())) {
            throw new RuntimeException('SUPPLIER_OPTIONS_TOOL_CALLER_TRANSACTION_UNVERIFIED');
        }

        $actor = null;
        $authorization = 'NOT_EVALUATED';
        $gate = 'NOT_EVALUATED';
        try {
            [$authorization, $actor] = $this->authorize($context, $input, $toolName);
            if ($authorization !== 'AUTHORIZED') {
                $result = $this->outcome($authorization === 'INVALID_TOOL_INPUT' ? 'error' : 'denied', $authorization);
            } else {
                $observation = $this->read->observe($context->problemCaseId);
                $gate = $observation->code;
                if ($observation->result === null) {
                    $result = $this->outcome($gate === 'CURRENT_STATE_UNDETERMINED' ? 'undetermined' : 'not_applicable', $gate);
                } else {
                    $result = [
                        'schema_version' => self::VERSION,
                        'status' => 'success',
                        'code' => 'ACTIVE',
                        'problem_case_id' => $context->problemCaseId,
                        'source_observed_at' => $observation->observedAt,
                        'data' => $this->projection->project($observation->result),
                    ];
                }
            }
        } catch (ModelNotFoundException) {
            $result = $this->outcome('error', 'CASE_NOT_FOUND');
        } catch (Throwable) {
            $result = $this->outcome('error', 'SUPPLIER_OPTIONS_FAILED');
        }

        // The composition has ended its snapshot, including exceptional paths.
        // Follow existing audit fail-fast conventions: no payload returns after audit failure.
        try {
            $this->audit->log('merlin_supplier_options_tool_called', properties: [
                'backend_user_id' => $context->userId,
                'problem_case_id' => $context->problemCaseId,
                'tool' => self::NAME,
                'contract_version' => self::VERSION,
                'input' => ['problem_case_id' => $context->problemCaseId],
                'invocation_id' => (string) Str::uuid(),
                'authorization_outcome' => $authorization,
                'resolver_gate_outcome' => $gate,
                'status' => $result['status'],
                'code' => $result['code'],
                'result_schema_version' => $result['schema_version'],
                'option_count' => isset($result['data']['options']) ? count($result['data']['options']) : null,
                'source_observed_at' => $result['source_observed_at'] ?? null,
                'supplier_evaluated_at' => $result['data']['evaluated_at'] ?? null,
            ], causer: $actor);
        } catch (Throwable) {
            throw new RuntimeException('SUPPLIER_OPTIONS_TOOL_AUDIT_FAILED');
        }

        return $result;
    }

    /** @return array{string, ?User} */
    private function authorize(SupplierOptionsToolContext $context, mixed $input, string $toolName): array
    {
        if ($toolName !== self::NAME) {
            return ['UNKNOWN_TOOL', null];
        }
        if (! is_array($input) || count($input) !== 1 || ! isset($input['problem_case_id'])
            || ! is_string($input['problem_case_id']) || ! Str::isUuid($input['problem_case_id'])) {
            return ['INVALID_TOOL_INPUT', null];
        }
        if ($input['problem_case_id'] !== $context->problemCaseId) {
            return ['CASE_CONTEXT_MISMATCH', null];
        }
        if (! in_array(self::NAME, $context->allowedTools, true)) {
            return ['AI_CAPABILITY_DENIED', null];
        }
        $actor = $this->users->findForSupplierOptionsTool($context->userId);
        if ($actor === null || ! Gate::forUser($actor)->allows('inventory.view')
            || ! Gate::forUser($actor)->allows('item-suppliers.view')) {
            return ['AUTHORIZATION_DENIED', $actor];
        }

        return ['AUTHORIZED', $actor];
    }

    /** @return array{schema_version: string, status: string, code: string, data: null} */
    private function outcome(string $status, string $code): array
    {
        return ['schema_version' => self::VERSION, 'status' => $status, 'code' => $code, 'data' => null];
    }
}
