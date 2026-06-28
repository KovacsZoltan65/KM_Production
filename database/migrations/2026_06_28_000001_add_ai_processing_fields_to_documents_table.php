<?php

use App\Enums\DocumentProcessingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('processing_status')
                ->default(DocumentProcessingStatus::Pending->value)
                ->after('approved');
            $table->decimal('processing_confidence', 5, 4)->nullable()->after('processing_status');
            $table->json('processing_result')->nullable()->after('processing_confidence');
            $table->json('processing_error')->nullable()->after('processing_result');
            $table->timestamp('processed_at')->nullable()->after('processing_error');

            $table->index('processing_status');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropIndex(['processing_status']);
            $table->dropColumn([
                'processing_status',
                'processing_confidence',
                'processing_result',
                'processing_error',
                'processed_at',
            ]);
        });
    }
};
