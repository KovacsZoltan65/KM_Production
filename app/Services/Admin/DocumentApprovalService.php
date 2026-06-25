<?php

namespace App\Services\Admin;

use App\Models\Document;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentApprovalService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function approve(Document $document, ?User $causer = null): Document
    {
        return DB::transaction(function () use ($document, $causer): Document {
            $document = Document::query()
                ->whereKey($document->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($document->approved) {
                throw ValidationException::withMessages(['document' => 'This document is already approved.']);
            }

            $document->update([
                'approved' => true,
                'approved_by' => $causer?->id,
                'approved_at' => now(),
            ]);

            $this->auditLogService->log('document_approved', $document, [
                'version' => $document->version,
                'document_type' => $document->document_type->value,
            ], $causer);

            return $document->refresh();
        });
    }
}
