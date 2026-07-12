Feladat:

A KM_Production projekt PHP-kódjában egészítsd ki a metódusokat részletes, magyar nyelvű PHPDoc kommentekkel és indokolt esetben rövid inline kommentekkel.

Cél:

A kommentek ne kizárólag a paraméter- és visszatérési típusokat dokumentálják, hanem mutassák be:

- a metódus üzleti célját;
- a végrehajtott műveletet;
- a jogosultság-ellenőrzés módját;
- a használt Service, Repository, Policy vagy FormRequest szerepét;
- a frontendnek átadott adatok célját;
- az auditnaplózás szerepét;
- az átirányítás vagy válasz jelentését;
- a fontosabb hibakimeneteket, például 403 vagy 404 válaszokat.

## Elvárt kommentelési szint

A kommentelés alapmintája:

```php
/**
 * Megjeleníti a kapacitástervezés dolgozói terhelés (Employees) oldalát.
 *
 * A művelet csak olyan felhasználók számára érhető el, akik rendelkeznek
 * a `capacity.view` jogosultsággal. Jogosultság hiányában 403 (Forbidden)
 * válasz kerül visszaadásra.
 *
 * Az oldal számára átadja az alkalmazottak aktuális kapacitás-terhelési
 * adatait, amelyeket a CapacityService biztosít.
 *
 * @param  Request  $request  Az aktuális HTTP kérés.
 * @return Response Inertia válasz az Employees oldalhoz.
 */
```

Ne használj önmagában ilyen minimális PHPDoc-ot:

```php
/**
 * @return array<int, array{label: string, value: string}>
 */
```

Helyette a privát segédmetódusoknál is legyen rövid üzleti vagy technikai leírás:

```php
/**
 * Összeállítja a választható dokumentumtípusok listáját.
 *
 * A DocumentType enum összes értékéből lokalizált label–value párokat
 * készít, amelyeket a frontend a dokumentumtípus kiválasztására szolgáló
 * lenyíló listákban használ.
 *
 * @return array<int, array{label: string, value: string}> A választható dokumentumtípusok listája.
 */
```

## Kommentelési szabályok

### 1. Nyelv

- Minden új PHPDoc és inline komment magyar nyelvű legyen.
- A kódnevek, osztálynevek, metódusnevek, permissionök, route-nevek és technikai azonosítók maradjanak angolul.
- A meglévő projekt i18n- és nyelvi konvencióit tartsd be.

### 2. Publikus controller metódusok

Minden publikus controller metódus PHPDoc-ja térjen ki az alábbiakra, amennyiben releváns:

- mit jelenít meg vagy milyen műveletet hajt végre;
- mely Policy vagy FormRequest ellenőrzi a jogosultságot;
- milyen permission szükséges;
- milyen Service végzi az üzleti logikát;
- milyen adatokat kap az Inertia oldal;
- történik-e auditnaplózás;
- hová irányít vissza sikeres művelet után;
- milyen HTTP választ ad vissza.

Példa egy index metódushoz:

```php
/**
 * Megjeleníti a gyáregységek adminisztrációs listaoldalát.
 *
 * A hozzáférést a FactoryUnitPolicy `viewAny` metódusa ellenőrzi.
 * Az oldal számára átadja a gyáregységek szűrt és lapozott listáját,
 * valamint az aktuálisan alkalmazott szűrőket.
 *
 * @param  IndexRequest  $request  A validált listaoldali kérés.
 * @return Response Inertia válasz a gyáregységek index oldalához.
 */
```

Példa egy létrehozó metódushoz:

```php
/**
 * Létrehoz egy új vevőt.
 *
 * A StoreCustomerRequest végzi a bemenet validálását és a szükséges
 * jogosultság ellenőrzését. A létrehozás üzleti logikáját a
 * CustomerService hajtja végre, amely auditnaplózás céljából megkapja
 * a műveletet végrehajtó felhasználót is.
 *
 * Sikeres mentés után visszatér az előző oldalra, és egy sikerüzenetet
 * jelenít meg.
 *
 * @param  StoreCustomerRequest  $request  A validált HTTP kérés.
 * @return RedirectResponse Visszairányítás az előző oldalra.
 */
```

Példa törléshez:

```php
/**
 * Törli a megadott dokumentumot.
 *
 * A művelet előtt a DocumentPolicy `delete` metódusa ellenőrzi,
 * hogy a bejelentkezett felhasználó jogosult-e a dokumentum törlésére.
 * A törlés üzleti logikáját a DocumentService végzi, amely a műveletet
 * auditnaplózás céljából a végrehajtó felhasználóhoz rendeli.
 *
 * Sikeres törlés után a dokumentumok listaoldalára irányít át.
 *
 * @param  Document  $document  A törlendő dokumentum.
 * @return RedirectResponse Átirányítás a dokumentumok listaoldalára.
 */
```

### 3. Privát segédmetódusok

A privát segédmetódusokhoz is írj leíró PHPDoc-ot.

A PHPDoc tartalmazza:

- mit állít össze vagy mit ad vissza;
- milyen adatforrásból dolgozik;
- mire használja az eredményt a frontend vagy a hívó kód;
- pontos generikus visszatérési típust.

Példa:

```php
/**
 * Összeállítja az aktív vevők választható listáját.
 *
 * Az aktív Customer rekordokból azonosítót, kódot, nevet és a frontend
 * kiválasztómezőiben megjeleníthető összetett címkét készít.
 *
 * @return Collection<int, array{id: int, code: string, name: string, label: string}>
 *     Az aktív vevők kiválasztási listája.
 */
```

### 4. Inline kommentek

Inline kommentet csak ott adj hozzá, ahol az segíti a működés megértését.

Hasznos inline kommentek:

- Policy ellenőrzés;
- különleges jogosultság;
- auditnaplózás;
- nem nyilvánvaló adattranszformáció;
- streamelt letöltés;
- speciális átirányítás;
- üzletileg fontos Service-hívás;
- opcionális vagy feltételes működés.

Példa:

```php
// Ellenőrzi, hogy a felhasználó jogosult-e a dokumentum letöltésére.
// A DocumentPolicy::download() metódust hívja meg.
$this->authorize('download', $document);
```

Ne kommentelj nyilvánvaló szintaktikai részleteket.

Kerülendő:

```php
// Visszaad egy tömböt.
return [];
```

### 5. Jogosultságok dokumentálása

Pontosan az aktuális kód működését dokumentáld.

Ha Policy működik:

```php
$this->authorize('viewAny', Employee::class);
```

akkor nevezd meg a megfelelő Policy metódust:

```text
Az EmployeePolicy `viewAny` metódusa ellenőrzi a hozzáférést.
```

Ha közvetlen permission-ellenőrzés történik:

```php
$request->user()?->can('capacity.view') ?: abort(403);
```

akkor ezt dokumentáld:

```text
A művelethez `capacity.view` jogosultság szükséges. Jogosultság hiányában
403 (Forbidden) válasz kerül visszaadásra.
```

Ha a FormRequest végzi az autorizációt, ne állítsd, hogy a controller Policy-t hív.

Példa:

```text
A ScheduleProductionOrderRequest végzi a bemenet validálását, valamint
a `capacity.plan` és feltételesen a `capacity.override` jogosultság
ellenőrzését.
```

### 6. Service és auditnaplózás

Csak akkor említs auditnaplózást, ha azt a kód vagy a kapcsolódó Service
ténylegesen alátámasztja.

Tipikus jel:

```php
$this->service->create($data, $request->user());
```

Ilyenkor megfogalmazható:

```text
A Service auditnaplózás céljából megkapja a műveletet végrehajtó
felhasználót is.
```

Ne találj ki auditműködést pusztán azért, mert User paraméter szerepel.
Ellenőrizd a Service implementációját, ha ez nem egyértelmű.

### 7. Visszatérési értékek

Az `@return` leírás legyen konkrét.

Jó példák:

```php
@return Response Inertia válasz a dokumentum adatlapjához.
```

```php
@return RedirectResponse Átirányítás a létrehozott dokumentum adatlapjára.
```

```php
@return StreamedResponse A dokumentum fájljának streamelt HTTP válasza.
```

Kerülendő:

```php
@return Response
```

### 8. Paraméterek

Minden paraméterhez írj rövid, pontos leírást.

Példa:

```php
@param  UpdateDocumentRequest  $request   A validált HTTP kérés.
@param  Document               $document  A módosítandó dokumentum.
```

### 9. Formázás

- Tartsd be a projekt meglévő PHP-CS-Fixer/Pint formázását.
- A sorhossz lehetőleg maradjon olvasható.
- A PHPDoc mondatok végén használj pontot.
- Ne használj felesleges HTML-t vagy Markdown felsorolást PHPDoc-on belül.
- Ne változtasd meg a metódusok működését pusztán a kommentelési feladat miatt.
- Ne nevezz át metódust, változót, osztályt vagy route-ot.
- Ne módosíts üzleti logikát.
- Ne készíts felesleges Service vagy Repository osztályt.

## Hallucináció elkerülése

A kommentek kizárólag a tényleges kódból és a kapcsolódó osztályokból
megállapítható működést írhatják le.

Mielőtt például azt írod, hogy:

- egy FormRequest jogosultságot ellenőriz;
- egy Service auditnaplózást végez;
- egy Policy adott permissiont használ;
- egy lekérdezés csak aktív rekordokat ad vissza;

ellenőrizd a kapcsolódó implementációt.

Ha valami nem állapítható meg biztosan, használj semleges megfogalmazást,
vagy ne említsd.

## Munkamenet

1. Vizsgáld meg a célfájlokat.
2. Nézd meg a kapcsolódó:
    - FormRequesteket;
    - Policy osztályokat;
    - Service-eket;
    - enumokat;
    - route-okat;
    - Inertia oldalakat.

3. Egészítsd ki a metódusokat részletes PHPDoc kommentekkel.
4. Adj inline kommenteket csak indokolt helyeken.
5. Ne módosíts működést.
6. Futtasd a releváns formázót és teszteket.

## Elfogadási feltételek

- Minden érintett publikus metódus rendelkezik részletes magyar PHPDoc-pal.
- A PHPDoc bemutatja a metódus célját és működését, nem csak a típusokat.
- A privát segédmetódusok visszatérési típusa mellett szerepel a céljuk is.
- A jogosultsági leírások megfelelnek a tényleges Policy-, permission- vagy FormRequest-logikának.
- Nem került a kommentekbe feltételezett vagy nem igazolt működés.
- A kommentek magyar nyelvűek.
- A kód működése nem változott.
- A formázás és a tesztek sikeresen lefutnak.

A végén adj rövid összefoglalót:

- mely fájlokat módosítottad;
- hány metódust dokumentáltál;
- milyen ellenőrzéseket futtattál;
- találtál-e olyan helyet, ahol a meglévő komment félrevezető vagy pontatlan volt.
