<?php

namespace App\Repositories\Admin;

use App\Enums\CustomerOrderStatus;
use App\Enums\ProductionOrderStatus;
use App\Enums\ProductionTaskStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\QualityCheckResult;
use App\Enums\StockMovementType;
use App\Enums\StockReservationStatus;
use App\Repositories\Contracts\ManufacturingIntelligenceRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ManufacturingIntelligenceRepository implements ManufacturingIntelligenceRepositoryInterface
{
    public function bottlenecks(): array
    {
        $reserved = DB::table('capacity_reservations')
            ->selectRaw('factory_unit_id, SUM(planned_minutes) as reserved_minutes')
            ->where('reserved_from', '>=', now()->startOfDay())
            ->where('reserved_from', '<', now()->copy()->addDays(7)->endOfDay())
            ->groupBy('factory_unit_id');

        $available = DB::table('factory_unit_calendars')
            ->selectRaw('factory_unit_id, SUM(CASE WHEN is_working_day = 1 THEN ((strftime("%s", work_end) - strftime("%s", work_start)) / 60) - break_minutes ELSE 0 END) as available_minutes')
            ->groupBy('factory_unit_id');

        if (DB::connection()->getDriverName() !== 'sqlite') {
            $available = DB::table('factory_unit_calendars')
                ->selectRaw('factory_unit_id, SUM(CASE WHEN is_working_day = 1 THEN TIMESTAMPDIFF(MINUTE, work_start, work_end) - break_minutes ELSE 0 END) as available_minutes')
                ->groupBy('factory_unit_id');
        }

        $queue = DB::table('production_tasks')
            ->join('operation_sequence_steps', 'operation_sequence_steps.id', '=', 'production_tasks.operation_sequence_step_id')
            ->selectRaw('operation_sequence_steps.factory_unit_id, COUNT(*) as queue_length')
            ->whereNotIn('production_tasks.status', [
                ProductionTaskStatus::Completed->value,
                ProductionTaskStatus::Cancelled->value,
                ProductionTaskStatus::Rejected->value,
            ])
            ->groupBy('operation_sequence_steps.factory_unit_id');

        $late = DB::table('production_orders')
            ->join('production_tasks', 'production_tasks.production_order_id', '=', 'production_orders.id')
            ->join('operation_sequence_steps', 'operation_sequence_steps.id', '=', 'production_tasks.operation_sequence_step_id')
            ->selectRaw('operation_sequence_steps.factory_unit_id, COUNT(DISTINCT production_orders.id) as late_related_orders')
            ->whereNull('production_orders.deleted_at')
            ->whereDate('production_orders.planned_finish_date', '<', now()->toDateString())
            ->whereNotIn('production_orders.status', [
                ProductionOrderStatus::Completed->value,
                ProductionOrderStatus::Cancelled->value,
            ])
            ->groupBy('operation_sequence_steps.factory_unit_id');

        $averageExpression = $this->minutesBetween('production_tasks.started_at', 'production_tasks.finished_at');
        $average = DB::table('production_tasks')
            ->join('operation_sequence_steps', 'operation_sequence_steps.id', '=', 'production_tasks.operation_sequence_step_id')
            ->selectRaw("operation_sequence_steps.factory_unit_id, AVG({$averageExpression}) as average_task_duration")
            ->whereNotNull('production_tasks.started_at')
            ->whereNotNull('production_tasks.finished_at')
            ->groupBy('operation_sequence_steps.factory_unit_id');

        $rows = DB::table('factory_units')
            ->leftJoinSub($reserved, 'reserved', fn ($join) => $join->on('reserved.factory_unit_id', '=', 'factory_units.id'))
            ->leftJoinSub($available, 'available', fn ($join) => $join->on('available.factory_unit_id', '=', 'factory_units.id'))
            ->leftJoinSub($queue, 'queue', fn ($join) => $join->on('queue.factory_unit_id', '=', 'factory_units.id'))
            ->leftJoinSub($late, 'late_orders', fn ($join) => $join->on('late_orders.factory_unit_id', '=', 'factory_units.id'))
            ->leftJoinSub($average, 'average_tasks', fn ($join) => $join->on('average_tasks.factory_unit_id', '=', 'factory_units.id'))
            ->whereNull('factory_units.deleted_at')
            ->selectRaw('factory_units.id, factory_units.code, factory_units.name, COALESCE(reserved.reserved_minutes, 0) as reserved_minutes, COALESCE(available.available_minutes, 2400) as available_minutes, COALESCE(queue.queue_length, 0) as queue_length, COALESCE(late_orders.late_related_orders, 0) as late_related_orders, average_tasks.average_task_duration')
            ->orderBy('factory_units.name')
            ->limit(100)
            ->get();

        return ['rows' => $rows->map(function (object $row): array {
            $available = max((float) $row->available_minutes, 1.0);
            $utilization = round(((float) $row->reserved_minutes / $available) * 100, 1);

            return [
                'factory_unit' => "{$row->code} - {$row->name}",
                'reserved_minutes' => (int) $row->reserved_minutes,
                'available_minutes' => (int) $row->available_minutes,
                'utilization_percent' => $utilization,
                'queue_length' => (int) $row->queue_length,
                'average_task_duration' => $row->average_task_duration === null ? null : round((float) $row->average_task_duration, 1),
                'late_related_orders' => (int) $row->late_related_orders,
                'status' => $this->bottleneckStatus($utilization),
            ];
        })->values()->all()];
    }

    public function materialForecast(): array
    {
        $stock = DB::table('stock_balances')
            ->selectRaw('item_id, SUM(quantity) as current_stock')
            ->groupBy('item_id');

        $reservations = DB::table('stock_reservations')
            ->selectRaw('item_id, SUM(reserved_quantity) as reserved_quantity')
            ->whereNull('deleted_at')
            ->where('status', StockReservationStatus::Active->value)
            ->groupBy('item_id');

        $consumption = DB::table('stock_movements')
            ->selectRaw('item_id, SUM(ABS(quantity)) / 30 as average_daily_consumption')
            ->where('movement_type', StockMovementType::ProductionConsume->value)
            ->where('performed_at', '>=', now()->subDays(30))
            ->groupBy('item_id');

        $rows = DB::table('items')
            ->leftJoinSub($stock, 'stock', fn ($join) => $join->on('stock.item_id', '=', 'items.id'))
            ->leftJoinSub($reservations, 'reservations', fn ($join) => $join->on('reservations.item_id', '=', 'items.id'))
            ->leftJoinSub($consumption, 'consumption', fn ($join) => $join->on('consumption.item_id', '=', 'items.id'))
            ->whereNull('items.deleted_at')
            ->selectRaw('items.id, items.item_number, items.name, items.unit, COALESCE(stock.current_stock, 0) as current_stock, COALESCE(reservations.reserved_quantity, 0) as reserved_quantity, consumption.average_daily_consumption')
            ->orderBy('items.item_number')
            ->limit(100)
            ->get();

        return ['rows' => $rows->map(function (object $row): array {
            $available = (float) $row->current_stock - (float) $row->reserved_quantity;
            $daily = $row->average_daily_consumption === null ? null : (float) $row->average_daily_consumption;
            $days = $daily !== null && $daily > 0 ? max(0.0, round($available / $daily, 1)) : null;

            return [
                'item_id' => $row->id,
                'item' => "{$row->item_number} - {$row->name}",
                'unit' => $row->unit,
                'current_stock' => (float) $row->current_stock,
                'reserved_quantity' => (float) $row->reserved_quantity,
                'available_quantity' => $available,
                'average_daily_consumption' => $daily === null ? null : round($daily, 3),
                'days_until_stockout' => $days,
                'risk_level' => $this->stockRisk($days),
            ];
        })->values()->all()];
    }

    public function supplierPerformance(): array
    {
        $deliveryDays = $this->daysBetween('purchase_orders.ordered_at', 'goods_receipts.received_at');

        $receipts = DB::table('purchase_orders')
            ->leftJoin('goods_receipts', function ($join): void {
                $join->on('goods_receipts.purchase_order_id', '=', 'purchase_orders.id')
                    ->whereNull('goods_receipts.deleted_at');
            })
            ->whereNull('purchase_orders.deleted_at')
            ->selectRaw("purchase_orders.supplier_id, COUNT(goods_receipts.id) as goods_receipts_count, AVG(CASE WHEN purchase_orders.ordered_at IS NOT NULL AND goods_receipts.received_at IS NOT NULL THEN {$deliveryDays} ELSE NULL END) as average_delivery_days, SUM(CASE WHEN purchase_orders.expected_delivery_date IS NOT NULL AND goods_receipts.received_at IS NOT NULL AND DATE(goods_receipts.received_at) > purchase_orders.expected_delivery_date THEN 1 ELSE 0 END) as late_delivery_count")
            ->groupBy('purchase_orders.supplier_id');

        $rows = DB::table('suppliers')
            ->leftJoin('purchase_orders', function ($join): void {
                $join->on('purchase_orders.supplier_id', '=', 'suppliers.id')
                    ->whereNull('purchase_orders.deleted_at');
            })
            ->leftJoinSub($receipts, 'receipts', fn ($join) => $join->on('receipts.supplier_id', '=', 'suppliers.id'))
            ->whereNull('suppliers.deleted_at')
            ->selectRaw('suppliers.id, suppliers.name as supplier, COUNT(purchase_orders.id) as purchase_orders_count, COALESCE(MAX(receipts.goods_receipts_count), 0) as goods_receipts_count, MAX(receipts.average_delivery_days) as average_delivery_days, COALESCE(MAX(receipts.late_delivery_count), 0) as late_delivery_count')
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderBy('suppliers.name')
            ->limit(100)
            ->get();

        return ['rows' => $rows->map(function (object $row): array {
            $receipts = (int) $row->goods_receipts_count;
            $late = (int) $row->late_delivery_count;

            return [
                'supplier' => $row->supplier,
                'purchase_orders_count' => (int) $row->purchase_orders_count,
                'goods_receipts_count' => $receipts,
                'average_delivery_days' => $row->average_delivery_days === null ? null : round((float) $row->average_delivery_days, 1),
                'late_delivery_count' => $late,
                'on_time_rate' => $receipts > 0 ? round((($receipts - $late) / $receipts) * 100, 1) : null,
            ];
        })->values()->all()];
    }

    public function qualityTrends(): array
    {
        $rows = DB::table('quality_checks')
            ->join('production_tasks', 'production_tasks.id', '=', 'quality_checks.production_task_id')
            ->join('production_orders', 'production_orders.id', '=', 'production_tasks.production_order_id')
            ->join('items', 'items.id', '=', 'production_orders.item_id')
            ->whereNull('production_orders.deleted_at')
            ->selectRaw('items.item_number, items.name as item_name, production_orders.order_number as production_order, SUM(CASE WHEN quality_checks.result = ? THEN 1 ELSE 0 END) as accepted_count, SUM(CASE WHEN quality_checks.result = ? THEN 1 ELSE 0 END) as rework_count, SUM(CASE WHEN quality_checks.result = ? THEN 1 ELSE 0 END) as rejected_count, SUM(CASE WHEN quality_checks.checked_at >= ? AND quality_checks.result IN (?, ?) THEN 1 ELSE 0 END) as recent_defects, SUM(CASE WHEN quality_checks.checked_at >= ? THEN 1 ELSE 0 END) as recent_total, SUM(CASE WHEN quality_checks.checked_at < ? AND quality_checks.checked_at >= ? AND quality_checks.result IN (?, ?) THEN 1 ELSE 0 END) as previous_defects, SUM(CASE WHEN quality_checks.checked_at < ? AND quality_checks.checked_at >= ? THEN 1 ELSE 0 END) as previous_total', [
                QualityCheckResult::Accepted->value,
                QualityCheckResult::ReworkRequired->value,
                QualityCheckResult::Rejected->value,
                now()->subDays(30),
                QualityCheckResult::Rejected->value,
                QualityCheckResult::ReworkRequired->value,
                now()->subDays(30),
                now()->subDays(30),
                now()->subDays(60),
                QualityCheckResult::Rejected->value,
                QualityCheckResult::ReworkRequired->value,
                now()->subDays(30),
                now()->subDays(60),
            ])
            ->groupBy('items.item_number', 'items.name', 'production_orders.order_number')
            ->orderByDesc('rejected_count')
            ->limit(100)
            ->get();

        return ['rows' => $rows->map(function (object $row): array {
            $accepted = (int) $row->accepted_count;
            $rework = (int) $row->rework_count;
            $rejected = (int) $row->rejected_count;
            $total = $accepted + $rework + $rejected;
            $recentRate = (int) $row->recent_total > 0 ? ((int) $row->recent_defects / (int) $row->recent_total) : null;
            $previousRate = (int) $row->previous_total > 0 ? ((int) $row->previous_defects / (int) $row->previous_total) : null;

            return [
                'item' => "{$row->item_number} - {$row->item_name}",
                'production_order' => $row->production_order,
                'accepted_count' => $accepted,
                'rework_count' => $rework,
                'rejected_count' => $rejected,
                'defect_rate' => $total > 0 ? round((($rework + $rejected) / $total) * 100, 1) : 0.0,
                'trend' => $this->trend($recentRate, $previousRate),
            ];
        })->values()->all()];
    }

    public function productionRisks(): array
    {
        $materialShortages = DB::table('material_requirements')
            ->join('customer_order_items', 'customer_order_items.id', '=', 'material_requirements.customer_order_item_id')
            ->selectRaw('customer_order_items.customer_order_id, SUM(material_requirements.missing_quantity) as missing_quantity')
            ->whereNull('material_requirements.deleted_at')
            ->groupBy('customer_order_items.customer_order_id');

        $qualityRejects = DB::table('quality_checks')
            ->join('production_tasks', 'production_tasks.id', '=', 'quality_checks.production_task_id')
            ->join('production_orders', 'production_orders.id', '=', 'production_tasks.production_order_id')
            ->join('customer_order_items', 'customer_order_items.id', '=', 'production_orders.customer_order_item_id')
            ->selectRaw('customer_order_items.customer_order_id, COUNT(*) as quality_rejects')
            ->whereIn('quality_checks.result', [QualityCheckResult::Rejected->value, QualityCheckResult::ReworkRequired->value])
            ->groupBy('customer_order_items.customer_order_id');

        $capacityDelay = DB::table('production_orders')
            ->join('customer_order_items', 'customer_order_items.id', '=', 'production_orders.customer_order_item_id')
            ->selectRaw('customer_order_items.customer_order_id, COUNT(*) as late_production_orders')
            ->whereNull('production_orders.deleted_at')
            ->whereDate('production_orders.planned_finish_date', '<', now()->toDateString())
            ->whereNotIn('production_orders.status', [ProductionOrderStatus::Completed->value, ProductionOrderStatus::Cancelled->value])
            ->groupBy('customer_order_items.customer_order_id');

        $supplierDelay = DB::table('purchase_orders')
            ->selectRaw('COUNT(*) as late_purchase_orders')
            ->whereNull('deleted_at')
            ->whereNotIn('status', [PurchaseOrderStatus::Received->value, PurchaseOrderStatus::Cancelled->value])
            ->whereDate('expected_delivery_date', '<', now()->toDateString());

        $globalSupplierDelay = (int) ($supplierDelay->first()->late_purchase_orders ?? 0);

        $rows = DB::table('customer_orders')
            ->join('customers', 'customers.id', '=', 'customer_orders.customer_id')
            ->leftJoinSub($materialShortages, 'shortages', fn ($join) => $join->on('shortages.customer_order_id', '=', 'customer_orders.id'))
            ->leftJoinSub($qualityRejects, 'quality', fn ($join) => $join->on('quality.customer_order_id', '=', 'customer_orders.id'))
            ->leftJoinSub($capacityDelay, 'capacity', fn ($join) => $join->on('capacity.customer_order_id', '=', 'customer_orders.id'))
            ->whereNull('customer_orders.deleted_at')
            ->whereNotIn('customer_orders.status', [CustomerOrderStatus::Completed->value, CustomerOrderStatus::Cancelled->value])
            ->selectRaw('customer_orders.id, customer_orders.order_number, customer_orders.status, customer_orders.requested_delivery_date, customers.name as customer, COALESCE(shortages.missing_quantity, 0) as missing_quantity, COALESCE(quality.quality_rejects, 0) as quality_rejects, COALESCE(capacity.late_production_orders, 0) as late_production_orders')
            ->orderBy('customer_orders.requested_delivery_date')
            ->limit(100)
            ->get();

        return ['rows' => $rows->map(function (object $row) use ($globalSupplierDelay): array {
            $days = $row->requested_delivery_date === null ? null : now()->startOfDay()->diffInDays(Carbon::parse($row->requested_delivery_date)->startOfDay(), false);
            $score = 0;
            $score += (float) $row->missing_quantity > 0 ? 35 : 0;
            $score += (int) $row->late_production_orders > 0 ? 25 : 0;
            $score += (int) $row->quality_rejects > 0 ? 15 : 0;
            $score += $globalSupplierDelay > 0 ? 10 : 0;
            $score += $days !== null && $days <= 3 ? 25 : ($days !== null && $days <= 7 ? 15 : 0);
            $score = min(100, $score);

            return [
                'customer_order' => $row->order_number,
                'customer' => $row->customer,
                'status' => $row->status,
                'requested_delivery_date' => $row->requested_delivery_date,
                'days_until_requested_delivery' => $days,
                'material_shortage' => (float) $row->missing_quantity,
                'capacity_delay' => (int) $row->late_production_orders,
                'quality_rejects' => (int) $row->quality_rejects,
                'supplier_delay' => $globalSupplierDelay,
                'risk_score' => $score,
                'risk_level' => $this->riskLevel($score),
            ];
        })->values()->all()];
    }

    public function procurementRecommendations(): array
    {
        $openPurchaseOrders = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->selectRaw('purchase_order_items.item_id, SUM(purchase_order_items.ordered_quantity - purchase_order_items.received_quantity) as open_quantity')
            ->whereNull('purchase_orders.deleted_at')
            ->whereIn('purchase_orders.status', [
                PurchaseOrderStatus::Draft->value,
                PurchaseOrderStatus::Ordered->value,
                PurchaseOrderStatus::PartiallyReceived->value,
            ])
            ->groupBy('purchase_order_items.item_id');

        $rows = DB::table('material_requirements')
            ->join('items', 'items.id', '=', 'material_requirements.required_item_id')
            ->join('customer_order_items', 'customer_order_items.id', '=', 'material_requirements.customer_order_item_id')
            ->join('customer_orders', 'customer_orders.id', '=', 'customer_order_items.customer_order_id')
            ->leftJoinSub($openPurchaseOrders, 'open_pos', fn ($join) => $join->on('open_pos.item_id', '=', 'material_requirements.required_item_id'))
            ->whereNull('material_requirements.deleted_at')
            ->whereNull('customer_orders.deleted_at')
            ->where('material_requirements.missing_quantity', '>', 0)
            ->selectRaw('items.id as item_id, items.item_number, items.name, items.unit, SUM(material_requirements.missing_quantity) as missing_quantity, COALESCE(MAX(open_pos.open_quantity), 0) as open_purchase_quantity')
            ->selectRaw($this->groupConcat('customer_orders.order_number').' as related_customer_orders')
            ->groupBy('items.id', 'items.item_number', 'items.name', 'items.unit')
            ->orderByDesc('missing_quantity')
            ->limit(100)
            ->get();

        return ['rows' => $rows->map(function (object $row): array {
            $recommended = max(0.0, (float) $row->missing_quantity - (float) $row->open_purchase_quantity);

            return [
                'item_id' => $row->item_id,
                'item' => "{$row->item_number} - {$row->name}",
                'unit' => $row->unit,
                'recommended_quantity' => $recommended,
                'reason' => (float) $row->open_purchase_quantity > 0
                    ? 'Missing material requirement partially covered by open purchase orders.'
                    : 'Missing material requirement has no open purchase coverage.',
                'risk_level' => $recommended > 0 ? 'critical' : 'warning',
                'related_customer_orders' => $row->related_customer_orders === null ? [] : explode(',', (string) $row->related_customer_orders),
            ];
        })->filter(fn (array $row): bool => $row['recommended_quantity'] > 0)->values()->all()];
    }

    public function leadTimeAccuracy(): array
    {
        $delayExpression = $this->daysBetween('production_orders.planned_finish_date', 'production_orders.finished_at');
        $rows = DB::table('production_orders')
            ->whereNull('deleted_at')
            ->whereNotNull('planned_finish_date')
            ->whereNotNull('finished_at')
            ->selectRaw("AVG({$delayExpression}) as average_delay, SUM(CASE WHEN DATE(finished_at) <= planned_finish_date THEN 1 ELSE 0 END) as on_time, SUM(CASE WHEN DATE(finished_at) < planned_finish_date THEN 1 ELSE 0 END) as early, SUM(CASE WHEN DATE(finished_at) > planned_finish_date THEN 1 ELSE 0 END) as late, COUNT(*) as total")
            ->first();

        $total = (int) ($rows->total ?? 0);

        if ($total < 3) {
            return ['enough_data' => false, 'message' => 'Not enough historical data'];
        }

        return [
            'enough_data' => true,
            'average_delay' => round((float) $rows->average_delay, 1),
            'on_time_rate' => round(((int) $rows->on_time / $total) * 100, 1),
            'early_completions' => (int) $rows->early,
            'late_completions' => (int) $rows->late,
        ];
    }

    private function bottleneckStatus(float $utilization): string
    {
        return match (true) {
            $utilization >= 90 => 'bottleneck',
            $utilization >= 75 => 'watch',
            default => 'normal',
        };
    }

    private function stockRisk(?float $days): string
    {
        return match (true) {
            $days === null => 'unknown',
            $days <= 3 => 'critical',
            $days <= 7 => 'warning',
            default => 'normal',
        };
    }

    private function riskLevel(int $score): string
    {
        return match (true) {
            $score >= 71 => 'high',
            $score >= 31 => 'medium',
            default => 'low',
        };
    }

    private function trend(?float $recentRate, ?float $previousRate): string
    {
        if ($recentRate === null || $previousRate === null) {
            return 'stable';
        }

        if ($recentRate > $previousRate) {
            return 'worsening';
        }

        if ($recentRate < $previousRate) {
            return 'improving';
        }

        return 'stable';
    }

    private function minutesBetween(string $start, string $end): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "(julianday({$end}) - julianday({$start})) * 24 * 60"
            : "TIMESTAMPDIFF(MINUTE, {$start}, {$end})";
    }

    private function daysBetween(string $start, string $end): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "julianday({$end}) - julianday({$start})"
            : "DATEDIFF({$end}, {$start})";
    }

    private function groupConcat(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "GROUP_CONCAT(DISTINCT {$column})"
            : "GROUP_CONCAT(DISTINCT {$column} ORDER BY {$column} SEPARATOR ',')";
    }
}
