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

Az `only` paraméterben megnevezett prop-oknak a vezérlőben lezárásoknak kell lenniük. A drága opció
a prop-oknak is lezárásoknak kell lenniük, hogy az Inertia kihagyhassa a lekérdezéseit egy
csak rekordokat tartalmazó újratöltés során. A kezdeti teljes kérés továbbra is kiértékeli az összes szükséges
prop-ot.

Tegyél közzé egy explicit betöltési állapotot, akadályozd meg az ismételt kéréseket, amíg aktív,
és állítsd vissza ezt az állapotot az `onFinish` függvényben. Tartsd láthatónak a meglévő rekordokat, és használd a
megosztott lokalizált kéréshiba-értesítést hibák esetén. Egy sikeres frissítéshez nincs szükség visszajelzésre, ha maga a frissített lista ad visszajelzést.
