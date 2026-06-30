<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveDocumentRequest;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\MakeCurrentDocumentRequest;
use App\Http\Requests\Admin\StoreDocumentRequest;
use App\Http\Requests\Admin\UpdateDocumentRequest;
use App\Models\Document;
use App\Services\Admin\DocumentService;
use App\Support\DocumentableRegistry;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $service) {}

    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', Document::class);

        return Inertia::render('Admin/Documents/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'documentTypeOptions' => $this->documentTypeOptions(),
            'documentableTypeOptions' => DocumentableRegistry::options(),
        ]);
    }

    public function show(Document $document): Response
    {
        $this->authorize('view', $document);

        return Inertia::render('Admin/Documents/Show', [
            'document' => $this->service->findForShow($document),
            'versions' => $this->service->versionsFor($document),
        ]);
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $document = $this->service->create($request->validated(), $request->file('file'), $request->user());

        return redirect()->route('admin.documents.show', $document)->with('success', 'Document uploaded.');
    }

    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        $this->service->update($document, $request->validated(), $request->user());

        return back()->with('success', 'Document updated.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        $this->service->delete($document, request()->user());

        return redirect()->route('admin.documents.index')->with('success', 'Document deleted.');
    }

    public function download(Document $document): StreamedResponse
    {
        $this->authorize('download', $document);

        return $this->service->download($document, request()->user());
    }

    public function approve(ApproveDocumentRequest $request, Document $document): RedirectResponse
    {
        $this->service->approve($document, $request->user());

        return back()->with('success', 'Document approved.');
    }

    public function makeCurrent(MakeCurrentDocumentRequest $request, Document $document): RedirectResponse
    {
        $this->service->makeCurrent($document, $request->user());

        return back()->with('success', 'Current document version changed.');
    }

    /**
     * @return array[]
     */
    private function documentTypeOptions(): array
    {
        return collect(DocumentType::cases())
            ->map(fn (DocumentType $type): array => [
                'label' => str($type->value)->replace('_', ' ')->title()->toString(),
                'value' => $type->value,
            ])
            ->values()
            ->all();
    }
}
