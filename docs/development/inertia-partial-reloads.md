# Inertia részleges újratöltések

Használd a `router.reload()` függvényt, ha egy meglévő Inertia oldalnak friss szerveradatokra van szüksége
az URL megváltoztatása vagy az aktuális Vue komponens újraépítése nélkül. Használd a
`router.get()` függvényt, ha a szűrés, rendezés vagy lapozás szándékosan megváltoztatja az
URL lekérdezést.

A listafrissítéseknek csak a változó prop-ot kell kérniük, és meg kell őrizniük a helyi felhasználói felület
állapotát:

```js
router.reload({
    only: ["records"],
    preserveState: true,
    preserveScroll: true,
});
```

Az `Admin/Employees/Index` oldal ennek a mintának a referencia-megvalósítása: a fejléc
frissítés gombja kizárólag a `records` prop-ot tölti újra.

A mintát jelenleg az Employees, Items, Customers, Suppliers, Stock Balances, Inventory / Shortages,
Inventory / Material Requirements, Inventory / Stock Reservations, Inventory / Stock Movements,
Customer Orders, Procurement / Purchase Requisitions, Factory Units, Locations, Professional Roles,
Operation Types, Users, Roles és Permissions admin listaoldalak használják.

Az Inventory dashboard jelenleg kizárólag frontendben definiált navigációs kártyákat jelenít meg,
page-local Inertia propot, adatbázis-lekérdezést és dashboard-cache-t nem használ. Emiatt nincs rajta
globális vagy szekciónkénti Frissítés gomb: a listaoldali `only: ["records"]` szerződés nem alkalmazható
nem létező dinamikus propra. A döntés és az újraauditálás feltételei az
[Inventory dashboard partial refresh szerződésben](inventory-dashboard-partial-refresh.md) találhatók.

Az Inventory / Shortages oldal lista propja a `records`. A `ShortageController` ezt lazy closure-ként
adja át, míg a változatlan `filters` prop kimarad a csak `records`-ot kérő partial payloadból; külön
option propja nincs. A lista közvetlenül a lapozott material requirement lekérdezésből készül, cache-t
nem olvas. A Playwright fixture egy `E2E-SHORTAGE-PARTIAL-REFRESH` jelölésű material requirement
`missing_quantity` értékét módosítja közvetlenül az izolált E2E SQLite adatbázisban.

Az Inventory / Material Requirements oldal szintén a `records` lista propot adja át lazy controller
closure-ként. A `filters`, `statusOptions`, `itemOptions` és `customerOrderOptions` kimarad a partial
payloadból; a tényleges repository-szűrők a `status`, `required_item_id` és `customer_order_id`. A
lapozott lista közvetlen, eager loadingot használó adatbázis-lekérdezésből készül, nem cache-ből. A
Playwright egy `E2E-MATERIAL-REQUIREMENT-PARTIAL-REFRESH` fixture-t required item szerint szűr, majd
a megjelenített `required_quantity` módosításával igazolja a friss adat betöltését.

Az Inventory / Stock Reservations oldal lista propja a `records`; a controller ezt és a
`statusOptions` selectoradatot lazy closure-ként adja át. A csak `records`-ot kérő partial payloadból
kimarad a `filters` és a `statusOptions`. A repository egyetlen tényleges listafiltere a `status`, a
rendezhető mezők az `id`, `item_id`, `reserved_quantity`, `status`, `reserved_at` és `released_at`, az
alapértelmezett rendezés `reserved_at desc`. A lapozott, kapcsolatait eager loadinggal betöltő lista
közvetlen adatbázis-lekérdezésből készül, nem cache-ből.

A release továbbra is az Inertia PATCH kérés szerveroldali redirectjével tölti újra egyszer az indexet,
ezért nincs külön siker utáni kézi reload. Csak aktív foglalás oldható fel; a művelet a státuszt és a
`released_at` mezőt módosítja, szükség esetén újraszámolja a kapcsolódó material requirementet,
auditbejegyzést készít, majd az inventory cache-doméneket invalidálja. A success flash változatlanul a
szerverről érkezik. A Playwright az `E2E-STOCK-RESERVATION-PARTIAL-REFRESH` itemhez tartozó izolált
foglalás `reserved_quantity` értékének módosításával igazolja a kézi partial refresht, a külön release
flow pedig az egyetlen PATCH kérést, a pending állapotot, az aktív státuszfilter megőrzését, a success
toastot és a felszabadított rekord helyes eltűnését ellenőrzi.

Az Inventory / Stock Movements read-only auditlista `records` propja, valamint a
`movementTypeOptions`, `itemOptions` és `locationOptions` selectorpropok lazy controller closure-k. A
csak `records`-ot kérő partial payloadból ezek az optionök és a `filters` kimaradnak. A tényleges
repository-filterek a `movement_type`, `item_id`, a forrás- vagy célraktárhelyre alkalmazott
`location_id`, továbbá az inkluzív `date_from` és `date_to`; a frontend által küldött `search` jelenleg
nem repository-filter. A rendezhető mezők az `id`, `item_id`, `quantity`, `movement_type` és
`performed_at`, az alapértelmezett sorrend `performed_at desc`. A lista az item, batch, instance,
forrás- és célraktárhely, valamint performer kapcsolatokat eager loadinggal, közvetlenül a
`stock_movements` táblából tölti, cache nélkül.

A Playwright az `E2E-STOCK-MOVEMENT-PARTIAL-REFRESH` item és az `E2E-SM-LOC` location alatt egy új,
`222.222` mennyiségű correction mozgást szúr be kizárólag az izolált E2E SQLite adatbázisba. A kézi
partial refresh után ellenőrzi az új sor egyszeri, időrendhelyes megjelenését és a filterek megőrzését.
A kézi refresh productionkódja nem hoz létre vagy módosít mozgást, Stock Balance rekordot vagy
üzleti auditbejegyzést.

A Procurement / Purchase Requisitions oldal lista propja a `records`; a controller ezt, a
`statusOptions` és az adatbázisból olvasott `itemOptions` propot lazy closure-ként adja át. A csak
`records`-ot kérő partial payloadból a `filters` és mindkét option prop kimarad. A repository tényleges
filterei a requisition numberre vagy notes mezőre alkalmazott `search` és a `status`; a rendezhető mezők
az `id`, `requisition_number`, `status`, `requested_at` és `created_at`, az alapértelmezett rendezés
`id asc`. A lapozott index a requestert eager loadinggal és az items kapcsolatot `withCount()`
aggregátummal tölti közvetlenül az adatbázisból, cache nélkül.

A rekord jóváhagyása és a Purchase Order generálása nem indexsorbeli action, hanem a Purchase
Requisition részletező oldal workflow-ja. Az approve Draft vagy Requested állapotból Approved állapotba
vált, `back()` redirectje egyetlen Inertia frissítést és a meglévő domain success flasht adja. A
generation kizárólag Approved igényből, kiválasztott supplierrel, tranzakciós row lock mellett hoz létre
egy Draft Purchase Ordert és annak tételeit, Ordered állapotba teszi az igényt és a generált rendelés
adatlapjára irányít. A frontend külön approve és generation pending guardot használ, nem végez optimista
státuszváltást vagy második reloadot. Mindkét workflow auditált, a `procurementChanged()` invalidációt
használja; maga az index nem cache-elt. Az elkülönített E2E fixture-ek a kézi partial frissítést, az
egyszeri approve kérést, valamint az egyszeri PO- és PO-item-generálást igazolják.

Az `only` paraméterben megnevezett prop-oknak a vezérlőben lezárásoknak kell lenniük. A drága opció
a prop-oknak is lezárásoknak kell lenniük, hogy az Inertia kihagyhassa a lekérdezéseit egy
csak rekordokat tartalmazó újratöltés során. A kezdeti teljes kérés továbbra is kiértékeli az összes szükséges
prop-ot.

A `records` prop mindig closure legyen, mert partial kéréskor csak így értékelhető ki célzottan.
Adatbázis-lekérdezést vagy érdemi transzformációt végző option propot szintén closure-ként adj át;
egyszerű, változatlan konstans propot nem szükséges csak ezért closure-be csomagolni.

Tegyél közzé egy explicit betöltési állapotot, akadályozd meg az ismételt kéréseket, amíg aktív,
és állítsd vissza ezt az állapotot az `onFinish` függvényben. Tartsd láthatónak a meglévő rekordokat, és használd a
megosztott lokalizált kéréshiba-értesítést hibák esetén. Egy sikeres frissítéshez nincs szükség visszajelzésre, ha maga a frissített lista ad visszajelzést.

Új listaoldal bekötésekor egészítsd ki a közös frontend szerződéstesztet a komponenssel és a
teljes kezdeti propkészlettel. A backend teszt igazolja a teljes Inertia payloadot, majd egy
`reloadOnly("records")` ellenőrzéssel azt is, hogy a filter- és option propok kimaradnak.
Playwrightban elkülönített E2E fixture rekordot módosíts közvetlenül a tesztadatbázisban, majd
ellenőrizd a `records` partial headert, a loading állapotot, a változatlan URL-t és keresést,
valamint a módosított adat megjelenését teljes dokumentumnavigáció nélkül.
