<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table): void {
            $table->foreignId('supplier_id')->nullable()->after('status')->constrained()->restrictOnDelete();
            $table->date('required_at')->nullable()->after('requested_at')->index();
            $table->date('proposed_supply_at')->nullable()->after('required_at')->index();
            $table->index(['supplier_id', 'required_at', 'proposed_supply_at'], 'pr_consolidation_group_index');
        });

        Schema::table('purchase_requisition_items', function (Blueprint $table): void {
            $table->decimal('quantity', 18, 3)->change();
        });

        Schema::create('purchase_requisition_item_proposal_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_requisition_item_id');
            $table->foreignId('supply_proposal_id');
            $table->decimal('quantity', 18, 3);
            $table->timestamps();

            $table->unique('supply_proposal_id', 'pr_item_proposal_source_proposal_uq');
            $table->foreign('purchase_requisition_item_id', 'pr_item_proposal_source_pr_item_fk')
                ->references('id')
                ->on('purchase_requisition_items')
                ->cascadeOnDelete();
            $table->foreign('supply_proposal_id', 'pr_item_proposal_source_proposal_fk')
                ->references('id')
                ->on('supply_proposals')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requisition_item_proposal_sources');

        Schema::table('purchase_requisition_items', function (Blueprint $table): void {
            $table->decimal('quantity', 12, 3)->change();
        });

        Schema::table('purchase_requisitions', function (Blueprint $table): void {
            $table->dropForeign(['supplier_id']);
        });

        Schema::table('purchase_requisitions', function (Blueprint $table): void {
            $table->dropIndex('pr_consolidation_group_index');
            $table->dropIndex(['required_at']);
            $table->dropIndex(['proposed_supply_at']);
            $table->dropColumn(['supplier_id', 'required_at', 'proposed_supply_at']);
        });
    }
};
