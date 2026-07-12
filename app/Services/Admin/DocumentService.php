<?php

namespace App\Services\Admin;

use App\Models\Document;
use App\Models\User;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Services\AuditLogService;
use App\Support\DocumentableRegistry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A dokumentumok tárolását, verziózását, letöltését és feldolgozását koordinálja.
 *
 * A metaadat-lekérdezést repository-ra, a jóváhagyást és verzióváltást célzott
 * szolgáltatásokra delegálja, a dokumentumműveleteket pedig auditnaplózza.
 */
class DocumentService
{
    public function __construct(
        private readonly DocumentRepositoryInterface $documents,
        private readonly DocumentVersionService $versions,
        private readonly DocumentApprovalService $approvals,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->documents->paginateForAdminIndex($filters, $perPage);
    }

    public function findForShow(Document $document): Document
    {
        return $this->documents->findForShow($document);
    }

    /**
     * @return Collection<int, Document>
     */
    public function versionsFor(Document $document): Collection
    {
        return $this->documents->versionsFor($document);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, UploadedFile $file, ?User $causer = null): Document
    {
        $documentableType = DocumentableRegistry::resolveExisting(
            (string) $attributes['documentable_type'],
            (int) $attributes['documentable_id']
        );

        $disk = 'local';
        $path = $file->store('documents', $disk);

        return DB::transaction(function () use ($attributes, $file, $causer, $documentableType, $disk, $path): Document {
            $group = [
                'documentable_type' => $documentableType,
                'documentable_id' => (int) $attributes['documentable_id'],
                'document_type' => (string) $attributes['document_type'],
            ];

            $version = $this->versions->nextVersionFor($group);
            $this->versions->clearCurrentFor($group);

            /** @var Document $document */
            $document = Document::query()->create([
                ...$group,
                'title' => $attributes['title'] ?? $file->getClientOriginalName(),
                'description' => $attributes['notes'] ?? null,
                'disk' => $disk,
                'path' => $path,
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'version' => $version,
                'is_current' => true,
                'approved' => false,
                'uploaded_by' => $causer?->id,
            ]);

            $this->auditLogService->log('document_uploaded', $document, [
                'version' => $version,
                'document_type' => $document->document_type->value,
                'documentable_type' => $document->documentable_type,
                'documentable_id' => $document->documentable_id,
            ], $causer);

            if ($version > 1) {
                $this->auditLogService->log('document_version_created', $document, [
                    'version' => $version,
                ], $causer);
            }

            return $document->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Document $document, array $attributes, ?User $causer = null): Document
    {
        $document = $this->documents->update($document, [
            'title' => $attributes['title'] ?? $document->title,
            'description' => $attributes['notes'] ?? $document->description,
        ]);

        $this->auditLogService->log('document_updated', $document, [
            'version' => $document->version,
        ], $causer);

        return $document;
    }

    public function delete(Document $document, ?User $causer = null): void
    {
        $hasNewerVersion = Document::query()
            ->where('documentable_type', $document->documentable_type)
            ->where('documentable_id', $document->documentable_id)
            ->where('document_type', $document->document_type->value)
            ->where('version', '>', $document->version)
            ->exists();

        if ($document->is_current && $hasNewerVersion) {
            throw ValidationException::withMessages(['document' => __('documents.validation.current_delete_newer')]);
        }

        $this->documents->delete($document);

        $this->auditLogService->log('document_deleted', $document, [
            'version' => $document->version,
        ], $causer);
    }

    public function approve(Document $document, ?User $causer = null): Document
    {
        return $this->approvals->approve($document, $causer);
    }

    public function makeCurrent(Document $document, ?User $causer = null): Document
    {
        return $this->versions->makeCurrent($document, $causer);
    }

    public function download(Document $document, ?User $causer = null): StreamedResponse
    {
        $disk = $document->disk ?? 'local';
        $path = $document->path ?? $document->file_path;

        if ($path === null || ! Storage::disk($disk)->exists($path)) {
            throw ValidationException::withMessages(['document' => __('documents.validation.file_missing')]);
        }

        $this->auditLogService->log('document_downloaded', $document, [
            'version' => $document->version,
        ], $causer);

        return Storage::disk($disk)->download($path, $document->original_filename ?? basename($path));
    }
}
