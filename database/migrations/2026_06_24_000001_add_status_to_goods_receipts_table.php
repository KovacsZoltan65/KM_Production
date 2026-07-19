<?php

use App\Enums\GoodsReceiptStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table): void {
            $table->string('status')->default(GoodsReceiptStatus::Draft->value)->index()->after('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
