<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupplyProposalStatus;
use App\Enums\SupplyStrategy;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StoreSupplyProposalRequest;
use App\Http\Requests\Admin\UpdateSupplyProposalRequest;
use App\Models\SupplyProposal;
use App\Services\Admin\SupplyProposalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplyProposalController extends Controller
{
    public function __construct(private readonly SupplyProposalService $service) {}

    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', SupplyProposal::class);

        return Inertia::render('Admin/SupplyProposals/Index', [
            'records' => fn () => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'itemOptions' => fn () => $this->service->itemOptions(),
            'supplierOptionsByItem' => fn () => $this->service->supplierOptionsByItem(),
            'strategyOptions' => collect(SupplyStrategy::cases())->map(fn (SupplyStrategy $strategy): array => [
                'label' => __("planning.supply_proposals.strategy.{$strategy->value}"),
                'value' => $strategy->value,
            ]),
            'statusOptions' => collect(SupplyProposalStatus::cases())->map(fn (SupplyProposalStatus $status): array => [
                'label' => __("planning.supply_proposals.status.{$status->value}"),
                'value' => $status->value,
            ]),
            'abilities' => [
                'create' => $request->user()->can('create', SupplyProposal::class),
                'update' => $request->user()->can('supply-proposals.update'),
                'approve' => $request->user()->can('supply-proposals.approve'),
                'cancel' => $request->user()->can('supply-proposals.delete'),
            ],
        ]);
    }

    public function store(StoreSupplyProposalRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', __('planning.supply_proposals.messages.created'));
    }

    public function update(UpdateSupplyProposalRequest $request, SupplyProposal $supplyProposal): RedirectResponse
    {
        $this->service->update($supplyProposal, $request->validated(), $request->user());

        return back()->with('success', __('planning.supply_proposals.messages.updated'));
    }

    public function propose(Request $request, SupplyProposal $supplyProposal): RedirectResponse
    {
        $this->authorize('propose', $supplyProposal);
        $this->service->propose($supplyProposal, $request->user());

        return back()->with('success', __('planning.supply_proposals.messages.proposed'));
    }

    public function approve(Request $request, SupplyProposal $supplyProposal): RedirectResponse
    {
        $this->authorize('approve', $supplyProposal);
        $this->service->approve($supplyProposal, $request->user());

        return back()->with('success', __('planning.supply_proposals.messages.approved'));
    }

    public function reject(Request $request, SupplyProposal $supplyProposal): RedirectResponse
    {
        $this->authorize('reject', $supplyProposal);
        $this->service->reject($supplyProposal, $request->user());

        return back()->with('success', __('planning.supply_proposals.messages.rejected'));
    }

    public function cancel(Request $request, SupplyProposal $supplyProposal): RedirectResponse
    {
        $this->authorize('cancel', $supplyProposal);
        $this->service->cancel($supplyProposal, $request->user());

        return back()->with('success', __('planning.supply_proposals.messages.cancelled'));
    }
}
