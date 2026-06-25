<?php

namespace App\Services\Admin;

use App\Models\Document;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class DocumentVersionService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array{documentable_type: string, documentable_id: int, document_type: string}  $group
     */
    public function nextVersionFor(array $group): int
    {
        $currentMax = Document::query()
            ->where($group)
            ->lockForUpdate()
            ->max('version');

        return ((int) $currentMax) + 1;
    }

    /**
     * @param  array{documentable_type: string, documentable_id: int, document_type: string}  $group
     */
    public function clearCurrentFor(array $group): void
    {
        Document::query()
            ->where($group)
            ->where('is_current', true)
            ->update(['is_current' => false]);
    }

    public function makeCurrent(Document $document, ?User $causer = null): Document
    {
        return DB::transaction(function () use ($document, $causer): Document {
            $document = Document::query()
                ->whereKey($document->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->clearCurrentFor($this->groupFor($document));
            $document->update(['is_current' => true]);

            $this->auditLogService->log('document_current_changed', $document, [
                'documentable_type' => $document->documentable_type,
                'documentable_id' => $document->documentable_id,
                'document_type' => $document->document_type->value,
                'version' => $document->version,
            ], $causer);

            return $document->refresh();
        });
    }

    /**
     * @return array{documentable_type: string, documentable_id: int, document_type: string}
     */
    public function groupFor(Document $document): array
    {
        return [
            'documentable_type' => $document->documentable_type,
            'documentable_id' => $document->documentable_id,
            'document_type' => $document->document_type->value,
        ];
    }
}
