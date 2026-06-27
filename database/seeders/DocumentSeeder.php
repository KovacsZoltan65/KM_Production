<?php

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Item;
use App\Models\ProductionOrder;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    /**
     * Seed sample document data.
     */
    public function run(): void
    {
        /** @var User $user */
        $user = User::query()->where('email', 'test@example.com')->first();
        /** @var Item $product */
        $product = Item::query()->where('item_number', 'PRODUCT-AAA')->firstOrFail();
        /** @var ProductionOrder $productionOrder */
        $productionOrder = ProductionOrder::query()->where('order_number', 'PO-2026-000001')->firstOrFail();

        $this->updateOrCreateDocument(
            $product,
            DocumentType::Drawing,
            'PRODUCT-AAA rajz',
            'drawings/product-aaa-v1.pdf',
            $user?->id,
        );

        $this->updateOrCreateDocument(
            $product,
            DocumentType::OperationDescription,
            'PRODUCT-AAA muveleti leiras',
            'operations/product-aaa-v1.pdf',
            $user?->id,
        );

        $this->updateOrCreateDocument(
            $productionOrder,
            DocumentType::WorkNote,
            'PO-2026-000001 gyartasi megjegyzes',
            'work-notes/po-2026-000001.txt',
            $user?->id,
        );
    }

    private function updateOrCreateDocument(
        Item|ProductionOrder $documentable,
        DocumentType $documentType,
        string $title,
        string $filePath,
        ?int $uploadedBy
    ): void {
        Document::query()->updateOrCreate(
            [
                'documentable_type' => $documentable::class,
                'documentable_id' => $documentable->id,
                'document_type' => $documentType->value,
                'title' => $title,
                'version' => 1,
            ],
            [
                'description' => null,
                'disk' => 'local',
                'path' => $filePath,
                'file_path' => $filePath,
                'original_filename' => basename($filePath),
                'mime_type' => str_ends_with($filePath, '.txt') ? 'text/plain' : 'application/pdf',
                'file_size' => null,
                'checksum' => hash('sha256', $filePath),
                'is_current' => true,
                'approved' => false,
                'uploaded_by' => $uploadedBy,
                'approved_by' => null,
                'approved_at' => null,
            ],
        );
    }
}
