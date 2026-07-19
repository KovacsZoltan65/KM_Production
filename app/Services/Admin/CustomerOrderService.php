<?php

namespace App\Services\Admin;

use App\Enums\CustomerOrderStatus;
use App\Models\CustomerOrder;
use App\Models\User;
use App\Repositories\Contracts\CustomerOrderRepositoryInterface;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A vevői rendelések létrehozását, módosítását és állapotváltásait koordinálja.
 *
 * A perzisztenciát repository-ra delegálja, a módosító műveleteket
 * tranzakcióban végzi és auditnaplózza.
 */
class CustomerOrderService
{
    public function __construct(
        private readonly CustomerOrderRepositoryInterface $repository,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Visszaadja a vevői rendelések szűrt és lapozott adminisztrációs listáját.
     *
     * @param  array{search?: string|null, status?: string|null, sort?: string|null,
     *     direction?: string|null}  $filters  Az alkalmazandó listaszűrők.
     * @return LengthAwarePaginator<int, covariant Model> A lapozott rendelések.
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForAdminIndex($filters, $perPage);
    }

    /**
     * Betölti a rendelés adatlapjához szükséges kapcsolatokat és darabszámokat.
     */
    public function findForShow(CustomerOrder $customerOrder): CustomerOrder
    {
        return $this->repository->findForShow($customerOrder);
    }

    /**
     * Létrehozza a vevői rendelést a validált tételsorokkal együtt.
     *
     * A repository tranzakcióban menti a fejlécet és a tételeket, majd a
     * szolgáltatás auditnaplózza a létrehozást.
     *
     * @param  array{customer_id: int, requested_delivery_date?: string|null,
     *     notes?: string|null, items: list<array{item_id: int,
     *     quantity: int|float|string, unit: string, notes?: string|null}>}  $payload
     *     A validált rendelési adatok.
     * @param  User|null  $causer  A műveletet végrehajtó felhasználó.
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
     * Frissíti a vevői rendelést, és teljesen újraépíti annak tételsorait.
     *
     * @param  array{customer_id: int, requested_delivery_date?: string|null,
     *     notes?: string|null, items: list<array{item_id: int,
     *     quantity: int|float|string, unit: string, notes?: string|null}>}  $payload
     *     A validált rendelési adatok.
     * @param  User|null  $causer  A műveletet végrehajtó felhasználó.
     */
    public function update(CustomerOrder $customerOrder, array $payload, ?User $causer = null): CustomerOrder
    {
        $items = $this->itemsFromPayload($payload);
        unset($payload['items']);

        $customerOrder = $this->repository->updateWithItems($customerOrder, $payload, $items);
        $this->auditLogService->log('customer_order_updated', $customerOrder, [], $causer);

        return $customerOrder;
    }

    /**
     * Megerősíti a piszkozat állapotú vevői rendelést.
     *
     * A tranzakció rögzíti a megerősítés időpontját és auditbejegyzést készít.
     *
     * @throws ValidationException Ha a rendelés nem piszkozat állapotú.
     */
    public function confirm(CustomerOrder $customerOrder, ?User $causer = null): CustomerOrder
    {
        $this->ensureStatus($customerOrder, [CustomerOrderStatus::Draft], __('orders.validation.only_draft_confirm'));

        return DB::transaction(function () use ($customerOrder, $causer): CustomerOrder {
            $customerOrder = $this->repository->confirm($customerOrder);
            $this->auditLogService->log('customer_order_confirmed', $customerOrder, [], $causer);

            return $customerOrder;
        });
    }

    /**
     * Visszavonja a még nem lezárt vevői rendelést.
     *
     * @throws ValidationException Ha a rendelés már teljesített vagy visszavont.
     */
    public function cancel(CustomerOrder $customerOrder, ?User $causer = null): CustomerOrder
    {
        if (\in_array($customerOrder->status, [CustomerOrderStatus::Completed, CustomerOrderStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'status' => __('orders.validation.completed_cancelled_cannot_cancel'),
            ]);
        }

        return DB::transaction(function () use ($customerOrder, $causer): CustomerOrder {
            $customerOrder = $this->repository->cancel($customerOrder);
            $this->auditLogService->log('customer_order_cancelled', $customerOrder, [], $causer);

            return $customerOrder;
        });
    }

    /**
     * Törli a piszkozat vagy visszavont állapotú vevői rendelést.
     *
     * @throws ValidationException Ha a rendelés állapota nem engedi a törlést.
     */
    public function delete(CustomerOrder $customerOrder, ?User $causer = null): void
    {
        if (! \in_array($customerOrder->status, [CustomerOrderStatus::Draft, CustomerOrderStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'status' => __('orders.validation.only_draft_cancelled_delete'),
            ]);
        }

        $this->auditLogService->log('customer_order_deleted', $customerOrder, [], $causer);
        $this->repository->delete($customerOrder);
    }

    /**
     * A validált tételsorokat repository-kompatibilis attribútumokká alakítja.
     *
     * @param  array{items?: list<array{item_id: int, quantity: int|float|string,
     *     unit: string, notes?: string|null}>}  $payload  A rendelési payload.
     * @return list<array{item_id: int, quantity: int|float|string, unit: string,
     *     notes: string|null}> A normalizált tételsorok.
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
     * Ellenőrzi, hogy a rendelés állapota szerepel-e az engedélyezett állapotok között.
     *
     * @param  list<CustomerOrderStatus>  $allowedStatuses  Az elfogadott állapotok.
     *
     * @throws ValidationException Ha az aktuális állapot nem engedélyezett.
     */
    private function ensureStatus(CustomerOrder $customerOrder, array $allowedStatuses, string $message): void
    {
        if (! \in_array($customerOrder->status, $allowedStatuses, true)) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }
}
