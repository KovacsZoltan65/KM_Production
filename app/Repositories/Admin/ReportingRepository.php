<?php

namespace App\Repositories\Admin;

use App\Enums\CustomerOrderStatus;
use App\Enums\GoodsReceiptStatus;
use App\Enums\ProductionOrderStatus;
use App\Enums\ProductionPlanStatus;
use App\Enums\ProductionTaskStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\QualityCheckResult;
use App\Enums\StockReservationStatus;
use App\Repositories\Contracts\ReportingRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportingRepository implements ReportingRepositoryInterface
{
    public function dashboardSummary(): array
    {
        return [
            'kpis' => [
                'open_customer_orders' => DB::table('customer_orders')->whereNull('deleted_at')->whereNotIn('status', [CustomerOrderStatus::Completed->value, CustomerOrderStatus::Cancelled->value])->count(),
                'active_production_plans' => DB::table('production_plans')->whereNull('deleted_at')->whereIn('status', [ProductionPlanStatus::Approved->value, ProductionPlanStatus::InProgress->value])->count(),
                'open_production_orders' => DB::table('production_orders')->whereNull('deleted_at')->whereNotIn('status', [ProductionOrderStatus::Completed->value, ProductionOrderStatus::Cancelled->value])->count(),
                'ready_production_tasks' => DB::table('production_tasks')->where('status', ProductionTaskStatus::Ready->value)->count(),
                'in_progress_tasks' => DB::table('production_tasks')->where('status', ProductionTaskStatus::InProgress->value)->count(),
                'waiting_for_qc' => DB::table('production_tasks')->where('status', ProductionTaskStatus::WaitingForCheck->value)->count(),
                'open_purchase_orders' => DB::table('purchase_orders')->whereNull('deleted_at')->whereIn('status', [PurchaseOrderStatus::Draft->value, PurchaseOrderStatus::Ordered->value, PurchaseOrderStatus::PartiallyReceived->value])->count(),
                'pending_goods_receipts' => DB::table('goods_receipts')->whereNull('deleted_at')->where('status', GoodsReceiptStatus::Draft->value)->count(),
                'shortages' => DB::table('material_requirements')->where('missing_quantity', '>', 0)->count(),
                'current_stock_value' => (float) DB::table('stock_balances')->sum('quantity'),
                'documents_waiting_approval' => DB::table('documents')->whereNull('deleted_at')->where('approved', false)->count(),
            ],
            'charts' => [
                'customer_orders_by_status' => $this->countsByStatus('customer_orders'),
                'production_orders_by_status' => $this->countsByStatus('production_orders'),
                'production_tasks_by_status' => $this->countsByStatus('production_tasks', false),
                'purchase_orders_by_status' => $this->countsByStatus('purchase_orders'),
                'quality_check_results' => $this->countsByStatus('quality_checks', false, 'result'),
            ],
        ];
    }

    public function customerOrdersSummary(array $filters = []): array
    {
        $query = DB::table('customer_orders')
            ->join('customers', 'customers.id', '=', 'customer_orders.customer_id')
            ->whereNull('customer_orders.deleted_at')
            ->select([
                'customer_orders.id',
                'customer_orders.order_number',
                'customer_orders.status',
                'customer_orders.created_at',
                'customer_orders.requested_delivery_date',
                'customers.name as customer_name',
            ]);

        if (($filters['status'] ?? null) !== null) {
            $query->where('customer_orders.status', $filters['status']);
        }

        if (($filters['customer_id'] ?? null) !== null) {
            $query->where('customer_orders.customer_id', $filters['customer_id']);
        }

        if (($filters['date_from'] ?? null) !== null) {
            $query->whereDate('customer_orders.created_at', '>=', $filters['date_from']);
        }

        if (($filters['date_to'] ?? null) !== null) {
            $query->whereDate('customer_orders.created_at', '<=', $filters['date_to']);
        }

        $rows = $query->orderByDesc('customer_orders.created_at')->limit(100)->get();

        return [
            'rows' => $rows->map(fn (object $row): array => [
                'id' => $row->id,
                'order_number' => $row->order_number,
                'customer' => $row->customer_name,
                'status' => $row->status,
                'created' => $row->created_at,
                'requested_delivery' => $row->requested_delivery_date,
                'days_open' => now()->startOfDay()->diffInDays(Carbon::parse($row->created_at)->startOfDay()),
            ])->values()->all(),
        ];
    }

    public function productionSummary(): array
    {
        $taskCounts = DB::table('production_tasks')
            ->selectRaw('production_order_id, COUNT(*) as all_tasks, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_tasks', [ProductionTaskStatus::Completed->value])
            ->groupBy('production_order_id');

        $rows = DB::table('production_orders')
            ->join('items', 'items.id', '=', 'production_orders.item_id')
            ->leftJoin('operation_sequence_steps', 'operation_sequence_steps.operation_sequence_id', '=', 'production_orders.operation_sequence_id')
            ->leftJoin('factory_units', 'factory_units.id', '=', 'operation_sequence_steps.factory_unit_id')
            ->leftJoinSub($taskCounts, 'task_counts', fn ($join) => $join->on('task_counts.production_order_id', '=', 'production_orders.id'))
            ->whereNull('production_orders.deleted_at')
            ->selectRaw('production_orders.id, production_orders.order_number, items.name as product, production_orders.status, MIN(factory_units.name) as factory_unit, production_orders.planned_start_date, production_orders.planned_finish_date, COALESCE(MAX(task_counts.completed_tasks), 0) as completed_tasks, COALESCE(MAX(task_counts.all_tasks), 0) as all_tasks')
            ->groupBy('production_orders.id', 'production_orders.order_number', 'items.name', 'production_orders.status', 'production_orders.planned_start_date', 'production_orders.planned_finish_date')
            ->orderByDesc('production_orders.created_at')
            ->limit(100)
            ->get();

        return [
            'rows' => $rows->map(fn (object $row): array => [
                'id' => $row->id,
                'production_order' => $row->order_number,
                'product' => $row->product,
                'status' => $row->status,
                'factory_unit' => $row->factory_unit ?? '-',
                'planned_start' => $row->planned_start_date,
                'planned_finish' => $row->planned_finish_date,
                'completed_tasks' => (int) $row->completed_tasks,
                'all_tasks' => (int) $row->all_tasks,
                'completed_percent' => (int) $row->all_tasks > 0 ? round(((int) $row->completed_tasks / (int) $row->all_tasks) * 100, 1) : 0,
            ])->values()->all(),
        ];
    }

    public function inventorySummary(): array
    {
        $reservations = DB::table('stock_reservations')
            ->selectRaw('item_id, location_id, SUM(reserved_quantity) as reserved')
            ->whereNull('deleted_at')
            ->where('status', StockReservationStatus::Active->value)
            ->groupBy('item_id', 'location_id');

        $rows = DB::table('stock_balances')
            ->join('items', 'items.id', '=', 'stock_balances.item_id')
            ->join('locations', 'locations.id', '=', 'stock_balances.location_id')
            ->leftJoinSub($reservations, 'reservations', function ($join): void {
                $join->on('reservations.item_id', '=', 'stock_balances.item_id')
                    ->on('reservations.location_id', '=', 'stock_balances.location_id');
            })
            ->selectRaw('items.item_number, items.name as item_name, locations.code as location_code, locations.name as location_name, SUM(stock_balances.quantity) as current_stock, COALESCE(MAX(reservations.reserved), 0) as reserved, COUNT(DISTINCT stock_balances.item_batch_id) as batch_count')
            ->groupBy('items.item_number', 'items.name', 'locations.code', 'locations.name')
            ->orderBy('items.item_number')
            ->limit(100)
            ->get();

        return [
            'rows' => $rows->map(fn (object $row): array => [
                'item' => "{$row->item_number} - {$row->item_name}",
                'location' => "{$row->location_code} - {$row->location_name}",
                'current_stock' => (float) $row->current_stock,
                'reserved' => (float) $row->reserved,
                'available' => (float) $row->current_stock - (float) $row->reserved,
                'batch_count' => (int) $row->batch_count,
                'is_shortage' => ((float) $row->current_stock - (float) $row->reserved) <= 0,
            ])->values()->all(),
        ];
    }

    public function procurementSummary(): array
    {
        $pendingReceipts = DB::table('goods_receipts')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'goods_receipts.purchase_order_id')
            ->whereNull('goods_receipts.deleted_at')
            ->where('goods_receipts.status', GoodsReceiptStatus::Draft->value)
            ->selectRaw('purchase_orders.supplier_id, COUNT(*) as pending_receipts')
            ->groupBy('purchase_orders.supplier_id');

        $rows = DB::table('suppliers')
            ->leftJoin('purchase_orders', function ($join): void {
                $join->on('purchase_orders.supplier_id', '=', 'suppliers.id')
                    ->whereNull('purchase_orders.deleted_at');
            })
            ->leftJoinSub($pendingReceipts, 'pending_receipts', fn ($join) => $join->on('pending_receipts.supplier_id', '=', 'suppliers.id'))
            ->whereNull('suppliers.deleted_at')
            ->selectRaw('suppliers.id, suppliers.name as supplier, COUNT(purchase_orders.id) as purchase_orders, SUM(CASE WHEN purchase_orders.status IN (?, ?, ?) THEN 1 ELSE 0 END) as open, SUM(CASE WHEN purchase_orders.status = ? THEN 1 ELSE 0 END) as closed, COALESCE(MAX(pending_receipts.pending_receipts), 0) as goods_receipts_pending', [
                PurchaseOrderStatus::Draft->value,
                PurchaseOrderStatus::Ordered->value,
                PurchaseOrderStatus::PartiallyReceived->value,
                PurchaseOrderStatus::Received->value,
            ])
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderBy('suppliers.name')
            ->limit(100)
            ->get();

        return ['rows' => $rows->map(fn (object $row): array => [
            'supplier' => $row->supplier,
            'purchase_orders' => (int) $row->purchase_orders,
            'open' => (int) $row->open,
            'closed' => (int) $row->closed,
            'goods_receipts_pending' => (int) $row->goods_receipts_pending,
        ])->values()->all()];
    }

    public function qualitySummary(): array
    {
        $rows = DB::table('production_orders')
            ->leftJoin('production_tasks', 'production_tasks.production_order_id', '=', 'production_orders.id')
            ->leftJoin('quality_checks', 'quality_checks.production_task_id', '=', 'production_tasks.id')
            ->whereNull('production_orders.deleted_at')
            ->selectRaw('production_orders.id, production_orders.order_number, COUNT(quality_checks.id) as quality_checks, SUM(CASE WHEN quality_checks.result = ? THEN 1 ELSE 0 END) as accepted, SUM(CASE WHEN quality_checks.result = ? THEN 1 ELSE 0 END) as rejected, SUM(CASE WHEN quality_checks.result = ? THEN 1 ELSE 0 END) as rework', [
                QualityCheckResult::Accepted->value,
                QualityCheckResult::Rejected->value,
                QualityCheckResult::ReworkRequired->value,
            ])
            ->groupBy('production_orders.id', 'production_orders.order_number')
            ->havingRaw('COUNT(quality_checks.id) > 0')
            ->orderByDesc('production_orders.created_at')
            ->limit(100)
            ->get();

        return ['rows' => $rows->map(fn (object $row): array => [
            'production_order' => $row->order_number,
            'quality_checks' => (int) $row->quality_checks,
            'accepted' => (int) $row->accepted,
            'rejected' => (int) $row->rejected,
            'rework' => (int) $row->rework,
            'acceptance_rate' => (int) $row->quality_checks > 0 ? round(((int) $row->accepted / (int) $row->quality_checks) * 100, 1) : 0,
        ])->values()->all()];
    }

    public function shopFloorSummary(): array
    {
        $driver = DB::connection()->getDriverName();
        $todayExpression = $driver === 'sqlite' ? "DATE('now')" : 'CURDATE()';
        $averageMinutesExpression = $driver === 'sqlite'
            ? '(julianday(production_tasks.finished_at) - julianday(production_tasks.started_at)) * 24 * 60'
            : 'TIMESTAMPDIFF(MINUTE, production_tasks.started_at, production_tasks.finished_at)';

        $rows = DB::table('employees')
            ->leftJoin('production_tasks', 'production_tasks.employee_id', '=', 'employees.id')
            ->whereNull('employees.deleted_at')
            ->selectRaw("employees.id, employees.name as employee, SUM(CASE WHEN production_tasks.status NOT IN (?, ?, ?) THEN 1 ELSE 0 END) as open_tasks, SUM(CASE WHEN production_tasks.status = ? THEN 1 ELSE 0 END) as in_progress, SUM(CASE WHEN production_tasks.status = ? AND DATE(production_tasks.finished_at) = {$todayExpression} THEN 1 ELSE 0 END) as completed_today, AVG(CASE WHEN production_tasks.started_at IS NOT NULL AND production_tasks.finished_at IS NOT NULL THEN {$averageMinutesExpression} ELSE NULL END) as average_task_time", [
                ProductionTaskStatus::Completed->value,
                ProductionTaskStatus::Cancelled->value,
                ProductionTaskStatus::Rejected->value,
                ProductionTaskStatus::InProgress->value,
                ProductionTaskStatus::Completed->value,
            ])
            ->groupBy('employees.id', 'employees.name')
            ->orderBy('employees.name')
            ->limit(100)
            ->get();

        return ['rows' => $rows->map(fn (object $row): array => [
            'employee' => $row->employee,
            'open_tasks' => (int) $row->open_tasks,
            'in_progress' => (int) $row->in_progress,
            'completed_today' => (int) $row->completed_today,
            'average_task_time' => $row->average_task_time === null ? null : round((float) $row->average_task_time, 1),
        ])->values()->all()];
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    private function countsByStatus(string $table, bool $softDeletes = true, string $column = 'status'): array
    {
        $query = DB::table($table)
            ->selectRaw("{$column} as label, COUNT(*) as value")
            ->groupBy($column)
            ->orderBy($column);

        if ($softDeletes) {
            $query->whereNull('deleted_at');
        }

        return $query->get()
            ->map(fn (object $row): array => [
                'label' => (string) $row->label,
                'value' => (int) $row->value,
            ])
            ->values()
            ->all();
    }
}
