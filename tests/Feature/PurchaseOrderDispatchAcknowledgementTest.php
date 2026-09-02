<?php

use App\Enums\PurchaseOrderDispatchStatus;
use App\Enums\PurchaseOrderItemStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SupplierAcknowledgementDeliveryDateVariance;
use App\Enums\SupplierAcknowledgementQuantityVariance;
use App\Enums\SupplierAcknowledgementStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDispatch;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierAcknowledgement;
use App\Models\User;
use App\Services\Admin\PurchaseOrderDispatchService;
use App\Services\Admin\SupplierAcknowledgementService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-09-01 12:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

/** @return array{order: PurchaseOrder, items: list<PurchaseOrderItem>, supplier: Supplier} */
function adr0016Order(int $itemCount = 1, array $orderAttributes = []): array
{
    $supplier = Supplier::factory()->create([
        'code' => 'SUP-0016-'.fake()->unique()->numerify('####'),
        'name' => 'ADR 0016 Supplier',
        'email' => 'orders@supplier.test',
        'is_active' => true,
    ]);
    $order = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'purchase_requisition_id' => null,
        'supplier_code_snapshot' => null,
        'supplier_name_snapshot' => null,
        'status' => PurchaseOrderStatus::Ordered,
        'ordered_at' => '2026-09-01 08:00:00',
        'expected_delivery_date' => '2026-09-10',
        ...$orderAttributes,
    ]);

    $items = [];
    foreach (range(1, $itemCount) as $index) {
        $items[] = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $order->id,
            'ordered_quantity' => sprintf('%d.000', $index * 10),
            'received_quantity' => '0.000',
            'unit' => 'kg',
            'status' => PurchaseOrderItemStatus::Ordered,
        ]);
    }

    return ['order' => $order->fresh(), 'items' => $items, 'supplier' => $supplier];
}

/** @return array<string, mixed> */
function adr0016DispatchAttributes(string $key = 'dispatch-key-1'): array
{
    return [
        'idempotency_key' => $key,
        'channel' => 'email',
        'recipient_name' => 'Supplier Desk',
        'recipient_email' => 'ORDERS@SUPPLIER.TEST',
        'attempted_at' => '2026-09-01 09:00:00',
        'dispatched_at' => '2026-09-01 09:01:00',
        'notes' => 'Sent outside KM Production.',
    ];
}

/** @param list<array<string, mixed>> $lines @return array<string, mixed> */
function adr0016AcknowledgementAttributes(array $lines, string $key = 'ack-key-1'): array
{
    return [
        'idempotency_key' => $key,
        'source' => 'email',
        'supplier_reference' => 'SUP-REF-0016',
        'acknowledged_by_name' => 'Supplier Agent',
        'acknowledgement_received_at' => '2026-09-01 10:00:00',
        'notes' => 'Recorded from the supplier response.',
        'lines' => $lines,
    ];
}

it('creates the ADR 0016 schema with all four append-only tables', function (): void {
    expect(Schema::hasColumns('purchase_order_dispatches', [
        'purchase_order_id', 'dispatch_sequence', 'idempotency_key', 'request_fingerprint',
        'previous_dispatch_id', 'supplier_code_snapshot', 'supplier_name_snapshot',
        'buyer_requested_delivery_date_snapshot', 'initiated_by',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('supplier_acknowledgements', [
            'purchase_order_id', 'purchase_order_dispatch_id', 'acknowledgement_sequence',
            'response_fingerprint', 'supersedes_acknowledgement_id', 'requires_follow_up',
            'requires_replanning', 'recorded_by',
        ]))->toBeTrue()
        ->and(Schema::hasTable('supplier_acknowledgement_items'))->toBeTrue()
        ->and(Schema::hasTable('supplier_acknowledgement_scope_items'))->toBeTrue();
});

it('records successful and failed dispatch history with immutable snapshots and compact audit', function (): void {
    ['order' => $order, 'supplier' => $supplier] = adr0016Order();
    $actor = User::factory()->create();
    $service = app(PurchaseOrderDispatchService::class);

    $failed = $service->recordFailed($order, [
        'idempotency_key' => 'dispatch-failed',
        'channel' => 'manual',
        'recipient_reference' => 'Buyer phone log',
        'attempted_at' => '2026-09-01 08:30:00',
        'failure_reason' => 'Supplier line unavailable.',
    ], $actor);
    $success = $service->recordSuccessful($order, [
        ...adr0016DispatchAttributes('dispatch-success'),
        'previous_dispatch_id' => $failed->id,
        'redispatch_reason' => 'Retry after failed phone handoff.',
    ], $actor);

    expect($failed->status)->toBe(PurchaseOrderDispatchStatus::Failed)
        ->and($failed->dispatch_sequence)->toBe(1)
        ->and($failed->dispatched_at)->toBeNull()
        ->and($success->status)->toBe(PurchaseOrderDispatchStatus::Succeeded)
        ->and($success->dispatch_sequence)->toBe(2)
        ->and($success->previous_dispatch_id)->toBe($failed->id)
        ->and($success->supplier_code_snapshot)->toBe($supplier->code)
        ->and($success->supplier_name_snapshot)->toBe($supplier->name)
        ->and($success->recipient_email)->toBe('orders@supplier.test')
        ->and($success->buyer_requested_delivery_date_snapshot?->toDateString())->toBe('2026-09-10')
        ->and($order->fresh()->status)->toBe(PurchaseOrderStatus::Ordered)
        ->and($order->fresh()->ordered_at?->format('Y-m-d H:i:s'))->toBe('2026-09-01 08:00:00')
        ->and(Activity::query()->where('event', 'purchase_order_dispatch_failed')->count())->toBe(1)
        ->and(Activity::query()->where('event', 'purchase_order_dispatched')->count())->toBe(1)
        ->and(Activity::query()->where('event', 'purchase_order_dispatched')->sole()->properties->has('recipient_email'))->toBeFalse();

    expect(fn () => $success->update(['notes' => 'changed']))->toThrow(LogicException::class)
        ->and(fn () => $success->delete())->toThrow(LogicException::class);
});

it('replays an exact dispatch before lifecycle eligibility and conflicts on changed payload', function (): void {
    ['order' => $order] = adr0016Order();
    $actor = User::factory()->create();
    $service = app(PurchaseOrderDispatchService::class);
    $attributes = adr0016DispatchAttributes('dispatch-replay');

    $first = $service->recordSuccessful($order, $attributes, $actor);
    $order->update(['status' => PurchaseOrderStatus::Received]);
    $replay = $service->recordSuccessful($order, $attributes, $actor);

    expect($replay->is($first))->toBeTrue()
        ->and(PurchaseOrderDispatch::query()->count())->toBe(1)
        ->and(Activity::query()->where('event', 'purchase_order_dispatched')->count())->toBe(1);

    expect(fn () => $service->recordSuccessful($order, [
        ...$attributes,
        'recipient_email' => 'different@supplier.test',
    ], $actor))->toThrow(ValidationException::class);
});

it('enforces redispatch predecessor and database sequence constraints', function (): void {
    ['order' => $order] = adr0016Order();
    $actor = User::factory()->create();
    $service = app(PurchaseOrderDispatchService::class);
    $first = $service->recordSuccessful($order, adr0016DispatchAttributes(), $actor);

    expect(fn () => $service->recordSuccessful($order, adr0016DispatchAttributes('dispatch-key-2'), $actor))
        ->toThrow(ValidationException::class);

    expect(fn () => DB::table('purchase_order_dispatches')->insert([
        ...$first->getRawOriginal(),
        'id' => $first->id + 100,
        'idempotency_key' => 'db-duplicate-sequence',
        'request_fingerprint' => str_repeat('a', 64),
    ]))->toThrow(QueryException::class);
});

it('allows legacy orders without ADR 0015 snapshots but rejects inactive suppliers', function (): void {
    ['order' => $order, 'supplier' => $supplier] = adr0016Order();
    $actor = User::factory()->create();
    $service = app(PurchaseOrderDispatchService::class);

    $dispatch = $service->recordSuccessful($order, adr0016DispatchAttributes(), $actor);
    expect($dispatch->supplier_name_snapshot)->toBe('ADR 0016 Supplier');

    ['order' => $otherOrder, 'supplier' => $otherSupplier] = adr0016Order();
    $otherSupplier->update(['is_active' => false]);
    expect(fn () => $service->recordSuccessful($otherOrder, adr0016DispatchAttributes('inactive-supplier'), $actor))
        ->toThrow(ValidationException::class);
});

it('records a linked accepted acknowledgement with exact matched variance', function (): void {
    ['order' => $order, 'items' => [$item]] = adr0016Order();
    $actor = User::factory()->create();
    $dispatch = app(PurchaseOrderDispatchService::class)->recordSuccessful($order, adr0016DispatchAttributes(), $actor);
    $attributes = adr0016AcknowledgementAttributes([[
        'purchase_order_item_id' => $item->id,
        'line_status' => 'accepted',
        'promised_quantity' => '10.000',
        'promised_delivery_date' => '2026-09-10',
    ]]);
    $attributes['purchase_order_dispatch_id'] = $dispatch->id;

    $ack = app(SupplierAcknowledgementService::class)->record($order, $attributes, $actor);
    $line = $ack->items->sole();

    expect($ack->status)->toBe(SupplierAcknowledgementStatus::Accepted)
        ->and($ack->requires_follow_up)->toBeFalse()
        ->and($ack->requires_replanning)->toBeFalse()
        ->and($ack->scopeItems)->toHaveCount(1)
        ->and($line->quantity_variance)->toBe(SupplierAcknowledgementQuantityVariance::Matched)
        ->and($line->quantity_variance_amount)->toBe('0.000')
        ->and($line->delivery_date_variance)->toBe(SupplierAcknowledgementDeliveryDateVariance::Matched)
        ->and($line->ordered_quantity_snapshot)->toBe('10.000')
        ->and($line->unit_snapshot)->toBe('kg')
        ->and($line->buyer_requested_delivery_date_snapshot?->toDateString())->toBe('2026-09-10')
        ->and(Activity::query()->where('event', 'supplier_acknowledgement_recorded')->count())->toBe(1);
});

it('rejects failed and wrong-order dispatch links', function (): void {
    ['order' => $order, 'items' => [$item]] = adr0016Order();
    ['order' => $otherOrder] = adr0016Order();
    $actor = User::factory()->create();
    $dispatchService = app(PurchaseOrderDispatchService::class);
    $failed = $dispatchService->recordFailed($order, [
        'idempotency_key' => 'failed-link',
        'channel' => 'manual',
        'recipient_reference' => 'phone',
        'attempted_at' => '2026-09-01 09:00:00',
        'failure_reason' => 'failed',
    ], $actor);
    $wrong = $dispatchService->recordSuccessful($otherOrder, adr0016DispatchAttributes('wrong-order'), $actor);
    $base = adr0016AcknowledgementAttributes([[
        'purchase_order_item_id' => $item->id,
        'line_status' => 'accepted',
        'promised_quantity' => '10.000',
    ]]);

    foreach ([$failed->id, $wrong->id] as $index => $dispatchId) {
        expect(fn () => app(SupplierAcknowledgementService::class)->record($order, [
            ...$base,
            'idempotency_key' => "invalid-dispatch-{$index}",
            'purchase_order_dispatch_id' => $dispatchId,
        ], $actor))->toThrow(ValidationException::class);
    }
});

it('requires attribution and notes for manual acknowledgement without dispatch', function (): void {
    ['order' => $order, 'items' => [$item]] = adr0016Order();
    $actor = User::factory()->create();
    $service = app(SupplierAcknowledgementService::class);
    $base = adr0016AcknowledgementAttributes([[
        'purchase_order_item_id' => $item->id,
        'line_status' => 'accepted',
        'promised_quantity' => '10.000',
    ]]);

    $ack = $service->record($order, $base, $actor);
    expect($ack->purchase_order_dispatch_id)->toBeNull();

    ['order' => $missingOrder, 'items' => [$missingItem]] = adr0016Order();
    expect(fn () => $service->record($missingOrder, [
        ...adr0016AcknowledgementAttributes([[
            'purchase_order_item_id' => $missingItem->id,
            'line_status' => 'accepted',
            'promised_quantity' => '10.000',
        ]], 'missing-attribution'),
        'supplier_reference' => null,
        'acknowledged_by_name' => null,
        'notes' => null,
    ], $actor))->toThrow(ValidationException::class);
});

it('models rejected and missing lines without zero-quantity ambiguity', function (): void {
    ['order' => $order, 'items' => [$first, $second]] = adr0016Order(2);
    $actor = User::factory()->create();
    $ack = app(SupplierAcknowledgementService::class)->record($order, adr0016AcknowledgementAttributes([[
        'purchase_order_item_id' => $first->id,
        'line_status' => 'rejected',
    ]]), $actor);

    expect($ack->status)->toBe(SupplierAcknowledgementStatus::AcceptedWithChanges)
        ->and($ack->requires_replanning)->toBeTrue()
        ->and($ack->scopeItems)->toHaveCount(2)
        ->and($ack->items)->toHaveCount(1)
        ->and($ack->items->sole()->promised_quantity)->toBeNull()
        ->and($ack->items->sole()->quantity_variance)->toBe(SupplierAcknowledgementQuantityVariance::Rejected);

    ['order' => $zeroOrder, 'items' => [$zeroItem]] = adr0016Order();
    expect(fn () => app(SupplierAcknowledgementService::class)->record($zeroOrder, adr0016AcknowledgementAttributes([[
        'purchase_order_item_id' => $zeroItem->id,
        'line_status' => 'accepted',
        'promised_quantity' => '0.000',
    ]], 'zero-is-invalid'), $actor))->toThrow(ValidationException::class);
});

it('calculates reduced and increased quantities and all promised-date comparisons', function (): void {
    $cases = [
        ['9.500', '2026-09-09', SupplierAcknowledgementQuantityVariance::Reduced, '-0.500', SupplierAcknowledgementDeliveryDateVariance::Earlier, true],
        ['10.000', '2026-09-10', SupplierAcknowledgementQuantityVariance::Matched, '0.000', SupplierAcknowledgementDeliveryDateVariance::Matched, false],
        ['11.250', '2026-09-10', SupplierAcknowledgementQuantityVariance::Increased, '1.250', SupplierAcknowledgementDeliveryDateVariance::Matched, false],
        ['10.000', '2026-09-11', SupplierAcknowledgementQuantityVariance::Matched, '0.000', SupplierAcknowledgementDeliveryDateVariance::Later, true],
        ['10.000', null, SupplierAcknowledgementQuantityVariance::Matched, '0.000', SupplierAcknowledgementDeliveryDateVariance::NotConfirmed, true],
    ];

    foreach ($cases as $index => [$quantity, $date, $quantityVariance, $amount, $dateVariance, $replanning]) {
        ['order' => $order, 'items' => [$item]] = adr0016Order();
        $ack = app(SupplierAcknowledgementService::class)->record($order, adr0016AcknowledgementAttributes([[
            'purchase_order_item_id' => $item->id,
            'line_status' => 'accepted',
            'promised_quantity' => $quantity,
            'promised_delivery_date' => $date,
        ]], "variance-{$index}"), User::factory()->create());
        $line = $ack->items->sole();

        expect($line->quantity_variance)->toBe($quantityVariance)
            ->and($line->quantity_variance_amount)->toBe($amount)
            ->and($line->delivery_date_variance)->toBe($dateVariance)
            ->and($ack->requires_replanning)->toBe($replanning);
    }

    ['order' => $nullDateOrder, 'items' => [$nullDateItem]] = adr0016Order(1, ['expected_delivery_date' => null]);
    $nullDateAck = app(SupplierAcknowledgementService::class)->record($nullDateOrder, adr0016AcknowledgementAttributes([[
        'purchase_order_item_id' => $nullDateItem->id,
        'line_status' => 'accepted',
        'promised_quantity' => '10.000',
        'promised_delivery_date' => '2026-09-10',
    ]], 'null-date-baseline'), User::factory()->create());

    expect($nullDateAck->items->sole()->delivery_date_variance)->toBe(SupplierAcknowledgementDeliveryDateVariance::NoBuyerBaseline)
        ->and($nullDateAck->requires_replanning)->toBeTrue();
});

it('creates full correction snapshots without inheriting omitted lines and resolves the effective version', function (): void {
    ['order' => $order, 'items' => [$first, $second]] = adr0016Order(2);
    $actor = User::factory()->create();
    $service = app(SupplierAcknowledgementService::class);
    $initial = $service->record($order, adr0016AcknowledgementAttributes([
        ['purchase_order_item_id' => $first->id, 'line_status' => 'accepted', 'promised_quantity' => '10.000'],
        ['purchase_order_item_id' => $second->id, 'line_status' => 'accepted', 'promised_quantity' => '20.000'],
    ]), $actor);
    $correction = $service->record($order, [
        ...adr0016AcknowledgementAttributes([
            ['purchase_order_item_id' => $first->id, 'line_status' => 'accepted', 'promised_quantity' => '9.000'],
        ], 'ack-correction'),
        'supersedes_acknowledgement_id' => $initial->id,
        'correction_reason' => 'Supplier corrected the first line.',
    ], $actor);

    expect($correction->acknowledgement_sequence)->toBe(2)
        ->and($correction->supersedes_acknowledgement_id)->toBe($initial->id)
        ->and($correction->scopeItems)->toHaveCount(2)
        ->and($correction->items)->toHaveCount(1)
        ->and($correction->status)->toBe(SupplierAcknowledgementStatus::AcceptedWithChanges)
        ->and($service->effectiveFor($order)?->is($correction))->toBeTrue()
        ->and($initial->fresh()->status)->toBe(SupplierAcknowledgementStatus::AcceptedWithChanges)
        ->and(Activity::query()->where('event', 'supplier_acknowledgement_superseded')->count())->toBe(1);

    expect(fn () => $service->record($order, [
        ...adr0016AcknowledgementAttributes([
            ['purchase_order_item_id' => $first->id, 'line_status' => 'accepted', 'promised_quantity' => '8.000'],
        ], 'stale-branch'),
        'supersedes_acknowledgement_id' => $initial->id,
        'correction_reason' => 'Stale correction.',
    ], $actor))->toThrow(ValidationException::class);
});

it('replays acknowledgement idempotently and detects key and response duplicates', function (): void {
    ['order' => $order, 'items' => [$item]] = adr0016Order();
    $actor = User::factory()->create();
    $service = app(SupplierAcknowledgementService::class);
    $attributes = adr0016AcknowledgementAttributes([[
        'purchase_order_item_id' => $item->id,
        'line_status' => 'accepted',
        'promised_quantity' => '10.000',
    ]]);
    $first = $service->record($order, $attributes, $actor);
    $order->update(['status' => PurchaseOrderStatus::Received]);

    expect($service->record($order, $attributes, $actor)->is($first))->toBeTrue()
        ->and($service->record($order, [...$attributes, 'idempotency_key' => 'new-key-same-response'], $actor)->is($first))->toBeTrue()
        ->and(SupplierAcknowledgement::query()->count())->toBe(1)
        ->and(Activity::query()->where('event', 'supplier_acknowledgement_recorded')->count())->toBe(1);

    expect(fn () => $service->record($order, [
        ...$attributes,
        'lines' => [[
            'purchase_order_item_id' => $item->id,
            'line_status' => 'accepted',
            'promised_quantity' => '9.000',
        ]],
    ], $actor))->toThrow(ValidationException::class);
});

it('does not mutate purchase order receiving inventory or MRP-authoritative fields', function (): void {
    ['order' => $order, 'items' => [$item]] = adr0016Order();
    $actor = User::factory()->create();
    $originalOrder = $order->getRawOriginal();
    $originalItem = $item->getRawOriginal();

    app(SupplierAcknowledgementService::class)->record($order, adr0016AcknowledgementAttributes([[
        'purchase_order_item_id' => $item->id,
        'line_status' => 'accepted',
        'promised_quantity' => '5.000',
        'promised_delivery_date' => '2026-09-20',
    ]]), $actor);

    $order->refresh();
    $item->refresh();
    expect($order->status)->toBe(PurchaseOrderStatus::Ordered)
        ->and($order->expected_delivery_date?->toDateString())->toBe('2026-09-10')
        ->and($item->ordered_quantity)->toBe($originalItem['ordered_quantity'])
        ->and($item->received_quantity)->toBe($originalItem['received_quantity'])
        ->and($item->status)->toBe(PurchaseOrderItemStatus::Ordered)
        ->and($order->ordered_at?->format('Y-m-d H:i:s'))->toBe(Carbon::parse($originalOrder['ordered_at'])->format('Y-m-d H:i:s'))
        ->and(GoodsReceipt::query()->count())->toBe(0)
        ->and(StockMovement::query()->count())->toBe(0);
});

it('enforces acknowledgement database checks and append-only model guards', function (): void {
    ['order' => $order, 'items' => [$item, $uncheckedItem]] = adr0016Order(2);
    $actor = User::factory()->create();
    $ack = app(SupplierAcknowledgementService::class)->record($order, adr0016AcknowledgementAttributes([[
        'purchase_order_item_id' => $item->id,
        'line_status' => 'accepted',
        'promised_quantity' => '10.000',
    ]]), $actor);

    expect(fn () => $ack->update(['notes' => 'changed']))->toThrow(LogicException::class)
        ->and(fn () => $ack->delete())->toThrow(LogicException::class)
        ->and(fn () => DB::table('supplier_acknowledgement_items')->insert([
            'supplier_acknowledgement_id' => $ack->id,
            'purchase_order_item_id' => $uncheckedItem->id,
            'line_status' => 'rejected',
            'promised_quantity' => '0.000',
            'promised_delivery_date' => null,
            'ordered_quantity_snapshot' => '10.000',
            'unit_snapshot' => 'kg',
            'buyer_requested_delivery_date_snapshot' => null,
            'quantity_variance' => 'rejected',
            'quantity_variance_amount' => null,
            'delivery_date_variance' => 'rejected',
            'created_at' => now(),
            'updated_at' => now(),
        ]))->toThrow(QueryException::class);
});

it('requires view plus the separate dispatch and acknowledge permissions', function (): void {
    seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    ['order' => $order] = adr0016Order();

    $user->givePermissionTo('purchase-orders.dispatch', 'purchase-orders.acknowledge');
    expect(Gate::forUser($user)->allows('dispatch', $order))->toBeFalse()
        ->and(Gate::forUser($user)->allows('acknowledge', $order))->toBeFalse();

    $user->givePermissionTo('procurement.view');
    expect(Gate::forUser($user)->allows('dispatch', $order))->toBeTrue()
        ->and(Gate::forUser($user)->allows('acknowledge', $order))->toBeTrue();
});
