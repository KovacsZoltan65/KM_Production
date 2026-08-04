# Inventory dashboard partial refresh szerződés

## 1. Jelenlegi dashboard-architektúra

Az Inventory dashboard útvonala a `GET /admin/inventory`, route-neve
`admin.inventory.index`. Az útvonal az
`App\Http\Controllers\Admin\Inventory\InventoryController::index()` metódust
hívja, amely a `StockBalance::viewAny` policy-n keresztül ellenőrzi az
`inventory.view` jogosultságot, majd page-local prop nélkül rendereli az
`Admin/Inventory/Index` Inertia komponenst.

A controller nem injektál service-t vagy repositoryt, nem futtat dashboard-
lekérdezést, és nem olvas cache-t. Az `App\Services\Admin\DashboardService` és
annak `dashboardSummary()` cache-e az általános admin dashboardhoz tartozik;
az Inventory dashboard nem használja.

## 2. Prop-leltár

A dashboardnak nincs page-local Inertia propja. A válaszban megjelenő adatok a
globális `HandleInertiaRequests` middleware shared propjai; nem Inventory KPI-k
vagy listák.

| Prop                               | Típus                            | UI / felhasználás                     | Adatforrás                          | Cache                                                | TTL             | Költség                              | Üzleti frissesség                                         |
| ---------------------------------- | -------------------------------- | ------------------------------------- | ----------------------------------- | ---------------------------------------------------- | --------------- | ------------------------------------ | --------------------------------------------------------- |
| page-local prop                    | nincs                            | nincs                                 | nincs                               | nincs                                                | N/A             | 0 dashboard-query                    | nincs runtime dashboard-adat                              |
| `errors`                           | validációs hibabag               | globális Inertia hibakezelés          | parent Inertia middleware / session | nincs üzleti cache                                   | N/A             | alacsony                             | következő request/session állapot                         |
| `auth.user`                        | object vagy `null`               | `AdminLayout`, felhasználói kontextus | aktuális request user               | nincs explicit alkalmazáscache                       | N/A             | közös requestköltség                 | bejelentkezés és useradat-változás                        |
| `auth.permissions`                 | `string[]`, lazy closure         | `AdminLayout` navigációs láthatóság   | Spatie Permission, aktuális user    | `spatie.permission.cache` a permission-definíciókhoz | 24 óra          | közös, userfüggő jogosultságfeloldás | role/permission/user-role mutáció; Spatie API invalidálja |
| `auth.roles`                       | `string[]`, lazy closure         | globális auth kontextus               | aktuális user role-kapcsolata       | nincs külön Inventory-cache                          | N/A             | közös, userfüggő kapcsolat           | user-role mutáció                                         |
| `preferences.locale`               | string                           | runtime fordítási locale              | alkalmazás/request locale           | nincs üzleti cache                                   | N/A             | konstans idejű                       | locale-preferencia változás                               |
| `preferences.availableLocales`     | konfigurációs lista              | globális locale-választó              | `config('app.available_locales')`   | Laravel konfigurációs környezet                      | deploymentfüggő | konstans idejű                       | konfiguráció/deployment                                   |
| `flash.success/error/warning/info` | string vagy `null`, lazy closure | globális toast                        | session                             | nincs üzleti cache                                   | request/session | alacsony                             | előző redirect által beállított flash                     |

A komponensben lévő `sections` nem Inertia prop, hanem frontend-konstans. Öt
navigációs kártyát definiál route-névvel, ikonnal és fordítási kulcsokkal. A
címkék locale-függők, de runtime `$t(...)` feloldással készülnek; nincs hozzájuk
adatbázis- vagy cache-lekérdezés.

## 3. UI-szekciók és adatcsoportok

| Szekció                  | Adat                      | Együtt frissítendő | Saját loading | Vizuális terület | Elavulás             | Újralekérési költség |
| ------------------------ | ------------------------- | ------------------ | ------------- | ---------------- | -------------------- | -------------------- |
| Oldalcím és leírás       | statikus translation key  | nem                | nincs         | fejléc           | fordítás/deployment  | nincs szerveradat    |
| Készletegyenlegek kártya | frontend-konstans + route | nem                | nincs         | egy kártya       | kód/route/deployment | nincs szerveradat    |
| Készletmozgások kártya   | frontend-konstans + route | nem                | nincs         | egy kártya       | kód/route/deployment | nincs szerveradat    |
| Készletfoglalások kártya | frontend-konstans + route | nem                | nincs         | egy kártya       | kód/route/deployment | nincs szerveradat    |
| Anyagszükségletek kártya | frontend-konstans + route | nem                | nincs         | egy kártya       | kód/route/deployment | nincs szerveradat    |
| Hiányok kártya           | frontend-konstans + route | nem                | nincs         | egy kártya       | kód/route/deployment | nincs szerveradat    |

Összesítő kártya, stock summary, shortage summary, reservation summary,
material requirement summary, recent movement lista, alert és táblázat jelenleg
nem létezik ezen az oldalon.

## 4. Konzisztenciahatárok

A navigációs kártyák egymástól független statikus klienskonfigurációk.
`atomic refresh required: no` mind az öt kártyára, mert nem írnak le közös
üzleti időpillanatot és nincs mögöttük dinamikus payload.

Az `auth.user`, `auth.permissions` és `auth.roles` globális requestkontextus;
ezek konzisztenciáját a közös Inertia middleware kezeli, nem az Inventory oldal
refresh-szerződése. A locale és az elérhető locale-lista szintén alkalmazásszintű
kontextus. Ezekből nem indokolt Inventory-specifikus refresh-csoportot képezni.

## 5. Cache-viselkedés

Az Inventory dashboardnak nincs cache-kulcsa, generációja, TTL-je vagy
invalidációs útvonala. A `km-production:dashboard:g{generation}:summary` kulcs a
fő admin dashboard cache-e, és nem adatforrása ennek az oldalnak.

A shared permission-definíciók Spatie kulcsa `spatie.permission.cache`, TTL-je
24 óra, invalidációját a Spatie mutációs API kezeli. Ez globális authorization-
infrastruktúra, nem Inventory dashboard-adat.

Kézi refresh szemantika: N/A. Nincs újraértékelendő dashboard-adat, ezért sem
normál cache-tisztelő reload, sem cache bypass, sem invalidálás nem indokolt.
`Cache::flush()` vagy új cache-invalidation szabály bevezetése tilos és
szükségtelen.

## 6. Értékelt stratégiák

Az 1 a legrosszabb, az 5 a legjobb érték az adott szempont szerint.

| Stratégia                                |  UX | Implementációs kockázat | Queryköltség | Konzisztencia | Tesztelhetőség | Karbantarthatóság | Összesen |
| ---------------------------------------- | --: | ----------------------: | -----------: | ------------: | -------------: | ----------------: | -------: |
| A. Egyetlen globális refresh             |   1 |                       3 |            2 |             3 |              3 |                 2 |       14 |
| B. Több adatcsoportonkénti refresh       |   1 |                       2 |            2 |             2 |              2 |                 1 |       10 |
| C. Globális és szekciónkénti kombináció  |   1 |                       1 |            1 |             2 |              1 |                 1 |        7 |
| D. Jelenleg nem indokolt partial refresh |   5 |                       5 |            5 |             5 |              5 |                 5 |       30 |

Az A–C megoldások olyan szerveradat- és loading-szerződést hoznának létre,
amelynek nincs jelenlegi adatforrása vagy felhasználói értéke. A D megőrzi az
oldal egyszerű navigációs szerepét, és nem sugall hamis frissességi garanciát.

## 7. Kiválasztott szerződés

**D. Jelenleg nem indokolt partial refresh.**

- Globálisan frissítendő page-local propok: `[]`.
- Szekciónként frissítendő page-local propok: `[]`.
- Dashboard lazy propok: nincsenek.
- Statikus dashboardadat: a frontend `sections` konstans és a translation
  key-k; ezekhez nem tartozik reload.
- Frissítés gomb: nincs globális és nincs szekciónkénti gomb.
- Cache: nincs dashboard-cache, bypass vagy invalidálás.
- Jogosultság: a route továbbra is minden kérésnél `inventory.view`
  authorizationt végez.

Az általános listaoldali `only: ['records']` minta nem alkalmazható, mert ezen
az oldalon nincs `records` vagy más dinamikus page-local prop.

## 8. Frontend loading és hibakezelés

Refresh hiányában nincs refresh-loading, disabled állapot, dupla kérés elleni
guard vagy refresh-toast. A meglévő navigáció változatlanul normál Inertia
linkeket használ.

Ha később dinamikus dashboard-adat kerül az oldalra, az alapértelmezett modell:
a meglévő adatok maradjanak láthatók, csak a refresh gomb legyen loading és
disabled, az aktív kérés alatt újabb refresh ne induljon. Hiba esetén
`notifyRequestError()` és `notifications.error.refresh_failed` használandó,
az adatok nem ürülhetnek ki, nyers exception nem jelenhet meg, siker esetén
nincs toast. Skeleton vagy teljes dashboard overlay csak mért, deferred
adatbetöltési igény esetén indokolt.

## 9. Jogosultsági viselkedés

A dashboard és mind az öt céloldal `viewAny` policy-je az `inventory.view`
permissiont követeli meg. Emiatt a jelenlegi kártyakészlet nem tartalmaz olyan
szekciót, amelyhez eltérő olvasási jogosultság tartozna. A foglalás feloldásának
`inventory.release` joga nem a dashboard megtekintési szerződésének része.

Minden teljes és jövőbeli partial Inertia kérés újra áthaladna a controller
authorizationjén. A frontend láthatóság nem helyettesíti a backend policy-t.

## 10. Tesztstratégia

A jelenlegi D döntést a meglévő feature tesztek igazolják: jogosult super admin
megkapja az `Admin/Inventory/Index` komponenst, jogosultság nélküli felhasználó
403 választ kap. Frontend refresh- és Playwright frissességi teszt jelenleg nem
alkalmazható, mert nincs dinamikus adat vagy refresh interakció.

Ha később dinamikus propok kerülnek a dashboardra, az implementáció előtt az
alábbi konkrét regressziós szerződés szükséges:

1. Backend feature teszt: teljes payload, pontos partial `only` payload,
   statikus propok hiánya, engedélyezett és 403 ág, cache hit/miss és releváns
   üzleti invalidálás, legalább egy külső adatváltozás.
2. Frontend unit teszt: pontos `only` lista, loading/disabled, dupla kérés
   blokkolása, hibaág, régi adatok megtartása és success toast hiánya.
3. Playwright: izolált külső adatváltozás legalább két valóban összetartozó
   dinamikus szekcióban, partial header, változatlan URL, statikus navigáció,
   loading és dokumentumnavigáció hiánya.
4. Cache regresszió: célzott generációváltás, cache hit/miss és annak igazolása,
   hogy nincs globális flush vagy indokolatlan bypass.

## 11. Teljesítmény

A page-local controllerútvonal közvetlen, read-only mérésben 0 adatbázis-queryt
futtatott. Tíz, bemelegítés utáni minta helyi Windows/Xdebug környezetben
6,339–280,452 ms között mozgott, 63,995 ms átlaggal. A nagy szórás miatt a wall
time nem tekinthető production latency-becslésnek; a stabil megállapítás a 0
dashboard-query és a dashboard-számítás hiánya.

A teljes HTTP-kérés auth-, session- és shared-prop költsége alkalmazásszintű,
nem Inventory-specifikus. Globális vagy szekciós refresh queryszám jelenleg N/A,
mert nincs refresh-propcsoport. Cache hit/miss és leglassabb adatcsoport szintén
N/A. Deferred prop bevezetése nem indokolt.

## 12. Későbbi fejlesztési döntési pont

Új audit szükséges, ha az oldalra tényleges KPI, összesítő, alert vagy recent
movement lista kerül. Akkor először a business-definíciót, adatforrást,
jogosultsági scope-ot, konzisztenciahatárt, queryköltséget és cache-invalidálást
kell lezárni; csak ezután választható globális vagy szekciónkénti refresh.

Az új audit nem nevezheti át a meglévő propokat, nem másolhatja automatikusan a
listaoldalak `records` szerződését, és nem vezethet be pollingot, Axios-hívást,
új API-t vagy cache bypass-t bizonyított igény nélkül.
