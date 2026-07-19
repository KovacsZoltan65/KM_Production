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
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $service) {}

    /**
     * Megjeleníti a dokumentumok adminisztrációs listaoldalát.
     *
     * A hozzáférést a DocumentPolicy `viewAny` metódusa ellenőrzi.
     * Az oldal számára átadja:
     * - a dokumentumok szűrt és lapozott listáját;
     * - az aktuálisan alkalmazott szűrőket;
     * - a dokumentumtípusok és kapcsolódó entitástípusok választható listáját.
     *
     * @param  IndexRequest  $request  A validált listaoldali kérés.
     * @return Response Inertia válasz a dokumentumok index oldalához.
     */
    public function index(IndexRequest $request): Response
    {
        // Ellenőrzi, hogy a bejelentkezett felhasználó jogosult-e
        // a dokumentumok listájának megtekintésére.
        //
        // A DocumentPolicy::viewAny() metódust hívja meg.
        $this->authorize('viewAny', Document::class);

        // A validált szűrőket lekéri a kérésből, hogy a dokumentumok listáját
        $filters = $request->filters();

        // Megjeleníti a dokumentumok adminisztrációs listaoldalát.
        return Inertia::render('Admin/Documents/Index', [
            // A dokumentumok szerveroldalon szűrt és lapozott listája.
            'records' => $this->service->paginateForAdminIndex(
                $filters,
                $request->perPage()
            ),

            // Visszaadja az aktuális szűrőket, hogy a frontend
            // megőrizhesse a szűrőmezők állapotát.
            'filters' => $filters,

            // A dokumentumtípusok listája a szűrőkhöz és
            // kiválasztómezőkhöz.
            'documentTypeOptions' => $this->documentTypeOptions(),

            // Azon entitástípusok listája, amelyekhez
            // dokumentum kapcsolható.
            'documentableTypeOptions' => DocumentableRegistry::options(),
        ]);
    }

    /**
     * Megjeleníti egy dokumentum részletes adatlapját.
     *
     * A hozzáférést a DocumentPolicy `view` metódusa ellenőrzi.
     * Az oldal számára átadja:
     * - a dokumentum részletes adatait;
     * - a dokumentum korábbi verzióinak listáját.
     *
     * @param  Document  $document  A megjelenítendő dokumentum.
     * @return Response Inertia válasz a dokumentum adatlapjához.
     */
    public function show(Document $document): Response
    {
        // Ellenőrzi, hogy a bejelentkezett felhasználó jogosult-e
        // a dokumentum megtekintésére.
        //
        // A DocumentPolicy::view() metódust hívja meg.
        $this->authorize('view', $document);

        // Megjeleníti a dokumentum részletes adatlapját.
        return Inertia::render('Admin/Documents/Show', [
            // A dokumentum részletes adatai.
            'document' => $this->service->findForShow($document),

            // A dokumentum verzióelőzményei.
            'versions' => $this->service->versionsFor($document),
        ]);
    }

    /**
     * Feltölt egy új dokumentumot.
     *
     * A bemenetet a StoreDocumentRequest validálja és jogosultság
     * szempontjából is ellenőrzi. A dokumentum létrehozását, a feltöltött
     * fájl feldolgozását és tárolását a DocumentService végzi, amely a
     * naplózáshoz megkapja a műveletet végrehajtó felhasználót is.
     *
     * Sikeres feltöltés után a létrehozott dokumentum adatlapjára
     * irányít át, és egy sikerüzenetet jelenít meg.
     *
     * @param  StoreDocumentRequest  $request  A validált HTTP kérés.
     * @return RedirectResponse Átirányítás a létrehozott dokumentum adatlapjára.
     */
    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        // Létrehozza az új dokumentumot, feldolgozza és eltárolja
        // a feltöltött fájlt, valamint audit naplózás céljából
        // átadja a bejelentkezett felhasználót.
        $document = $this->service->create(
            $request->validated(),
            $request->file('file'),
            $request->user()
        );

        // Átirányít a létrehozott dokumentum adatlapjára
        // sikeres feltöltési üzenettel.
        return redirect()
            ->route('admin.documents.show', $document)
            ->with('success', __('documents.messages.uploaded'));
    }

    /**
     * Frissíti egy meglévő dokumentum adatait.
     *
     * A bemenetet az UpdateDocumentRequest validálja és jogosultság
     * szempontjából is ellenőrzi. A módosítás üzleti logikáját a
     * DocumentService végzi, amely a naplózáshoz megkapja a műveletet
     * végrehajtó felhasználót is.
     *
     * Sikeres mentés után visszatér az előző oldalra, és egy sikerüzenetet
     * jelenít meg.
     *
     * @param  UpdateDocumentRequest  $request  A validált HTTP kérés.
     * @param  Document  $document  A módosítandó dokumentum.
     * @return RedirectResponse Visszairányítás az előző oldalra.
     */
    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        // Frissíti a dokumentum adatait a validált bemenet alapján.
        // A bejelentkezett felhasználó átadásra kerül audit naplózás céljából.
        $this->service->update(
            $document,
            $request->validated(),
            $request->user()
        );

        // Visszatér az előző oldalra sikeres módosítási üzenettel.
        return back()->with('success', __('documents.messages.updated'));
    }

    /**
     * Törli a megadott dokumentumot.
     *
     * A művelet előtt a DocumentPolicy `delete` metódusa ellenőrzi,
     * hogy a bejelentkezett felhasználó jogosult-e a dokumentum
     * törlésére. A törlés üzleti logikáját a DocumentService végzi,
     * amely a naplózáshoz megkapja a műveletet végrehajtó felhasználót is.
     *
     * Sikeres törlés után a dokumentumok listaoldalára irányít át,
     * és egy sikerüzenetet jelenít meg.
     *
     * @param  Document  $document  A törlendő dokumentum.
     * @return RedirectResponse Átirányítás a dokumentumok listaoldalára.
     */
    public function destroy(Document $document): RedirectResponse
    {
        // Ellenőrzi, hogy a felhasználó jogosult-e a dokumentum törlésére.
        // A DocumentPolicy::delete() metódust hívja meg.
        $this->authorize('delete', $document);

        // Törli a dokumentumot, és átadja a műveletet végrehajtó
        // felhasználót audit naplózás céljából.
        $this->service->delete($document, request()->user());

        // Átirányít a dokumentumok listaoldalára sikeres törlési üzenettel.
        return redirect()->route('admin.documents.index')->with('success', __('documents.messages.deleted'));
    }

    /**
     * Letölti a megadott dokumentumhoz tartozó fájlt.
     *
     * A művelet előtt a DocumentPolicy `download` metódusa ellenőrzi,
     * hogy a bejelentkezett felhasználó jogosult-e a dokumentum
     * letöltésére. A letöltés üzleti logikáját a DocumentService végzi,
     * amely a műveletet audit naplózás céljából a végrehajtó
     * felhasználóhoz is hozzárendeli.
     *
     * @param  Document  $document  A letöltendő dokumentum.
     * @return StreamedResponse A dokumentum fájljának streamelt HTTP válasza.
     */
    public function download(Request $request, Document $document): StreamedResponse
    {
        // Ellenőrzi, hogy a felhasználó jogosult-e a dokumentum
        // letöltésére.
        //
        // A DocumentPolicy::download() metódust hívja meg.
        $this->authorize('download', $document);

        // Elindítja a dokumentum fájljának letöltését, és átadja
        // a bejelentkezett felhasználót audit naplózás céljából.
        return $this->service->download(
            $document,
            $request->user(),
        );
    }

    /**
     * Jóváhagyja a megadott dokumentumot.
     *
     * A bemenetet az ApproveDocumentRequest validálja és jogosultság
     * szempontjából is ellenőrzi. A jóváhagyás üzleti logikáját a
     * DocumentService végzi, amely a műveletet a végrehajtó
     * felhasználóhoz rendeli audit naplózás céljából.
     *
     * Sikeres jóváhagyás után visszatér az előző oldalra, és egy
     * sikerüzenetet jelenít meg.
     *
     * @param  ApproveDocumentRequest  $request  A validált HTTP kérés.
     * @param  Document  $document  A jóváhagyandó dokumentum.
     * @return RedirectResponse Visszairányítás az előző oldalra.
     */
    public function approve(ApproveDocumentRequest $request, Document $document): RedirectResponse
    {
        // Jóváhagyja a dokumentumot, és átadja a műveletet
        // végrehajtó felhasználót audit naplózás céljából.
        $this->service->approve($document, $request->user());

        // Visszatér az előző oldalra sikeres jóváhagyási üzenettel.
        return back()->with('success', __('documents.messages.approved'));
    }

    /**
     * Aktívvá teszi a megadott dokumentumverziót.
     *
     * A bemenetet a MakeCurrentDocumentRequest validálja és jogosultság
     * szempontjából is ellenőrzi. A verzióváltás üzleti logikáját a
     * DocumentService végzi, amely a műveletet a végrehajtó
     * felhasználóhoz rendeli audit naplózás céljából.
     *
     * Sikeres verzióváltás után visszatér az előző oldalra, és egy
     * sikerüzenetet jelenít meg.
     *
     * @param  MakeCurrentDocumentRequest  $request  A validált HTTP kérés.
     * @param  Document  $document  Az aktívvá teendő dokumentumverzió.
     * @return RedirectResponse Visszairányítás az előző oldalra.
     */
    public function makeCurrent(MakeCurrentDocumentRequest $request, Document $document): RedirectResponse
    {
        // Aktívvá teszi a kiválasztott dokumentumverziót, és átadja
        // a műveletet végrehajtó felhasználót audit naplózás céljából.
        $this->service->makeCurrent(
            $document,
            $request->user(),
        );

        // Visszatér az előző oldalra sikeres verzióváltási üzenettel.
        return back()->with(
            'success',
            __('documents.messages.current_changed'),
        );
    }

    /**
     * Visszaadja a választható dokumentumtípusok listáját.
     *
     * A DocumentType enum értékeiből lokalizált label–value párokat
     * állít elő a frontend kiválasztómezői számára.
     *
     * @return list<array{label: string, value: string}>
     */
    private function documentTypeOptions(): array
    {
        return collect(DocumentType::cases())
            ->map(fn (DocumentType $type): array => [
                'label' => __("enum.document_type.{$type->value}"),
                'value' => $type->value,
            ])
            ->values()
            ->all();
    }
}
