<?php

namespace App\Services\Admin;

use App\Enums\CustomerOrderStatus;
use App\Models\CustomerOrder;
use App\Models\User;
use App\Repositories\Contracts\CustomerOrderRepositoryInterface;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerOrderService
{
    public function __construct(
        private readonly CustomerOrderRepositoryInterface $repository,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForAdminIndex($filters, $perPage);
    }

    public function findForShow(CustomerOrder $customerOrder): CustomerOrder
    {
        return $this->repository->findForShow($customerOrder);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, ?User $causer = null): CustomerOrder
    {
        $items = $this->itemsFromPayload($payload);
        unset($payload['items']);
        $payload['created_by'] = $causer?->id;

        $customerOrder = $this->repository->createWithItems($payload, $items);
        $this->auditLogService->log('customer_order_created', $customerOrder, [], $causer);

        return $customerOrder;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(CustomerOrder $customerOrder, array $payload, ?User $causer = null): CustomerOrder
    {
        $items = $this->itemsFromPayload($payload);
        unset($payload['items']);

        $customerOrder = $this->repository->updateWithItems($customerOrder, $payload, $items);
        $this->auditLogService->log('customer_order_updated', $customerOrder, [], $causer);

        return $customerOrder;
    }

    public function confirm(CustomerOrder $customerOrder, ?User $causer = null): CustomerOrder
    {
        $this->ensureStatus($customerOrder, [CustomerOrderStatus::Draft], 'Only draft customer orders can be confirmed.');

        return DB::transaction(function () use ($customerOrder, $causer): CustomerOrder {
            $customerOrder = $this->repository->confirm($customerOrder);
            $this->auditLogService->log('customer_order_confirmed', $customerOrder, [], $causer);

            return $customerOrder;
        });
    }

    public function cancel(CustomerOrder $customerOrder, ?User $causer = null): CustomerOrder
    {
        if (in_array($customerOrder->status, [CustomerOrderStatus::Completed, CustomerOrderStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'status' => 'Completed or already cancelled customer orders cannot be cancelled.',
            ]);
        }

        return DB::transaction(function () use ($customerOrder, $causer): CustomerOrder {
            $customerOrder = $this->repository->cancel($customerOrder);
            $this->auditLogService->log('customer_order_cancelled', $customerOrder, [], $causer);

            return $customerOrder;
        });
    }

    public function delete(CustomerOrder $customerOrder, ?User $causer = null): void
    {
        if (! in_array($customerOrder->status, [CustomerOrderStatus::Draft, CustomerOrderStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only draft or cancelled customer orders can be deleted.',
            ]);
        }

        $this->auditLogService->log('customer_order_deleted', $customerOrder, [], $causer);
        $this->repository->delete($customerOrder);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function itemsFromPayload(array $payload): array
    {
        return collect($payload['items'] ?? [])
            ->map(fn (array $item): array => [
                'item_id' => $item['item_id'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
                'notes' => $item['notes'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, CustomerOrderStatus>  $allowedStatuses
     */
    private function ensureStatus(CustomerOrder $customerOrder, array $allowedStatuses, string $message): void
    {
        if (! in_array($customerOrder->status, $allowedStatuses, true)) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }
}
