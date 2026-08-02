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
Customer Orders, Factory Units, Locations, Professional Roles, Operation Types, Users, Roles és
Permissions admin listaoldalak használják.

Az Inventory / Shortages oldal lista propja a `records`. A `ShortageController` ezt lazy closure-ként
adja át, míg a változatlan `filters` prop kimarad a csak `records`-ot kérő partial payloadból; külön
option propja nincs. A lista közvetlenül a lapozott material requirement lekérdezésből készül, cache-t
nem olvas. A Playwright fixture egy `E2E-SHORTAGE-PARTIAL-REFRESH` jelölésű material requirement
`missing_quantity` értékét módosítja közvetlenül az izolált E2E SQLite adatbázisban.

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
