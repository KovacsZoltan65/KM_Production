<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('disk')->nullable()->after('description');
            $table->string('path')->nullable()->after('disk');
            $table->string('checksum', 64)->nullable()->after('file_size');
            $table->boolean('approved')->default(false)->after('is_current');

            $table->index(['documentable_type', 'documentable_id', 'document_type', 'version'], 'documents_group_version_index');
            $table->index(['approved', 'is_current'], 'documents_approval_current_index');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropIndex('documents_group_version_index');
            $table->dropIndex('documents_approval_current_index');
            $table->dropColumn(['disk', 'path', 'checksum', 'approved']);
        });
    }
};
