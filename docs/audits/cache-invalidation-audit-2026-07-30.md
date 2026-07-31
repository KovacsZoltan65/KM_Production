# Cache-invalidation audit — 2026-07-30

## Scope

A teljes `app`, `routes`, `config`, `bootstrap`, `database`, `tests`,
`resources/js`, `.github/workflows` és `.env.example` cache-használata, továbbá
az érintett üzleti write service-ek és repository-aggregátumok kerültek
átvizsgálásra. Kiinduló ág: `Cache-invalidation_matrix`; kiinduló commit:
`f3efd21` (`Merge branch 'messages'`). A munkafa az audit elején tiszta volt,
CI-004 vagy más idegen módosítás nem volt jelen.

Commit, push és pull request nem készült.

A hosszú regressziós futások alatt a közös munkafában felhasználói/IDE
módosításként jelent meg a `.gitignore`, az `app/Enums/OperationTypeCode.php` és
az `nbproject/`. Ezek nem részei ennek a feladatnak, nem kerültek módosításra
vagy visszaállításra. A jelenlegi enumállapoton az Operation Type célzott
cache-teszt külön újrafutva zöld.

## Kiinduló cache-leltár

- 18 `Cache::remember()` üzleti bejegyzés.
- 18 implicit payloadolvasási útvonal és egy generációolvasás minden
  kulcsképzésnél.
- 15 üzleti cache-domain, 15 generációs counter és egy Spatie permission cache.
- TTL: 11 dashboard/report/capacity bejegyzés 60 másodperc; 7 Manufacturing
  Intelligence bejegyzés 5 perc; permission cache 24 óra.
- Modulok: dashboard, reporting, inventory, procurement, production, quality,
  workforce, capacity és Manufacturing Intelligence.
- Default production store: `database`; teszt: `array`; perzisztens contract:
  `file`.
- Cache tag, Redis-követelmény és üzleti `Cache::flush()` nem volt.
- Prettus repository cache: kikapcsolva.
- Selector, option, procurement dashboard és operatív inventory lista:
  alkalmazáscache nélkül.

A részletes tulajdonos, forrás, kulcs, TTL, fogyasztó, invalidáció és teszt a
[kanonikus mátrixban](../architecture/cache-invalidation-matrix.md) található.

## Cache-kulcsok és cache-családok

A meglévő domain-generációs formátum megmaradt:

```text
km-production:{domain}:g{generation}:{name}:{parameter-hash}
```

A 18 nyers névhasználatot név szerinti `BusinessCacheKey` metódus váltotta fel.
A normalizálás most rekurzív, az asszociatív és filterlista-sorrend kanonikus.
A `null`, üres és hiányzó érték nem ütközik. Locale/user/factory scope
különbsége unit contracttal védett, bár a jelenlegi üzleti payloadok globálisak.
Objektumot vagy rendezetlen tömböt tényleges cache-kulcs nem tartalmazott.

## Üzleti események

A mátrix 29 esemény- vagy időbeli szabályt fed le:

- törzsadat és partner: factory unit, location, employee, professional role,
  operation type, customer, supplier, item, BOM és operation sequence;
- inventory: material requirement, reservation, release/consume, movementet
  létrehozó goods receipt és material consumption;
- procurement: PR, PO, goods receipt és supplier;
- production/quality: order/plan/task, task material és quality check;
- capacity: schedule/override és a jelenleg külső write belépési pont nélküli
  calendarkorlát;
- document, permission, preference/locale;
- puszta időmúlás mint dokumentált TTL-only szabály.

## Igazolt kezdeti problémák

| Probléma | Felhasználói kockázat | Gyökérok | Bizonyíték | Javítás |
| --- | --- | --- | --- | --- |
| Anyagfelhasználás után stale inventory/forecast | A UI a fogyasztás előtti készletet mutatta | A service stock balance-t és movementet módosított invalidálás nélkül | kikapcsolt invalidálással `10.0`, helyes új érték `6.0` | tranzakción belül regisztrált `inventoryChanged()` 🔧 |
| Operation Type átnevezés után stale capacity schedule label | Régi műveletnév maradt az ütemezésben | Az admin service-nek nem volt `afterWrite()` hatása | capacity generation contract | `operationTypesChanged()` 🔧 |
| Customer névváltozás capacity cache-t is forgatott | Felesleges capacity újraszámítás | A customer master ugyanazt a széles metódust használta, mint az order workflow | unrelated capacity negatív teszt | `customersChanged()` szűkített scope 🔧 |
| Supplier master túl széles procurement hatást használt | Inventory/risk/recommendation felesleges újraszámítás | Master és workflow esemény összevonása | inventory generation megőrzési teszt | `suppliersChanged()` 🔧 |
| Workforce invalidáció bottleneck/intelligence domaint is forgatott | Felesleges intelligence újraszámítás | A bottleneck query nem olvas employee/role adatot | repository-forrásaudit | scope szűkítve shop-floor + capacity területre 🔧 |
| `null`, üres és hiányzó filter ütközött | Eltérő lekérési szerződés ugyanazt a payloadot kaphatta | Top-level `array_filter()` eltávolította az értékeket | kulcsgenerálási contract | típus- és értékmegőrző rekurzív normalizálás 🔧 |
| Cache entry nevek az owner service-ekben maradtak | Új owner eltérő névvel elszakadhatott az architektúrától | A központi osztály csak generikus `make()` API-t adott | architecture scan | 18 név szerinti kulcsmetódus 🔧 |

Normál üzleti folyamatban túl széles teljes flush nem volt. Purchase Requisition
write után viszont a procurement család konzervatívan forog, miközben a jelenlegi
cache queryk PR táblát nem olvasnak; ez mérhető hit-rate kockázat, nem stale-adat
hiba.

## Megvalósított architektúra

- `BusinessCacheKey`: központi, név szerinti kulcskatalógus és determinisztikus
  paraméter-normalizálás.
- `BusinessCacheInvalidator`: driverfüggetlen domain-generáció, explicit
  customer/supplier/operation-type scope.
- Dinamikus családok: generációváltás; a régi payloadok saját TTL-lel ürülnek.
- Cache tag és teljes flush: tiltott.
- Architecture teszt: az `app` alatt tiltja a `Cache::flush()` hívást, és a
  cache ownereknél tiltja a generikus nyers `make()` használatot.
- Stampede: új lock nem került be. A jelenlegi 60 s/5 perc TTL és a rendelkezésre
  álló tesztadat nem bizonyított lockot indokló terhelést.

## Tranzakciós stratégia

Nyitott tranzakció esetén a generációváltás `DB::afterCommit()` callback.
Közvetlen service write után az invalidátor szinkron fut. Igazolt esetek:

- sikeres material consumption commit után a generation nő és a következő
  inventory read `6.0`;
- kézi rollback után a DB balance és a cache-generáció egyaránt változatlan;
- külső tranzakcióba ágyazott Customer Order service rollbackje sem adatot, sem
  generációt nem hagy meg;
- szimulált cache-store hiba a commitolt Customer Order és item sorokat nem
  görgeti vissza, de a hibát nem nyeli el.

## Javított workflow-k

- Törzsadat: Operation Type; központi item/customer/supplier kulcs- és
  invalidációs scope.
- Inventory: production material consumption.
- Procurement: supplier master scope pontosítása; PO és GR meglévő lánca
  eredményalapú teszttel védett.
- Production/quality/capacity: task finish, quality check és capacity generation
  eredményalapú regresszióval védett.
- Reporting/intelligence: inventory, supplier performance, quality trend és
  kapcsolt parent dashboard generációk.
- Documents és permissions: meglévő helyes viselkedés megtartva.
- Selectorok: nincs meglévő cache, ezért új selector cache nem került be.

## Tesztek

### Célzott contractok

- Kulcssorrend, nested/list normalizálás, eltérő filter, null/üres/hiányzó,
  locale, user és factory scope.
- Empty-result CO report, Item label, Goods Receipt, material consumption,
  reservation release, PO close, supplier update, customer unrelated capacity,
  operation type, task finish és quality check.
- Rollback, nested rollback, cache-hiba utáni DB-integritás.
- Array és file driver; Spatie permission.
- Architecture: 2 teszt, 332 assertion.

### Hit/miss mérés

A `cache hit and post invalidation recalculation contract` repository-hívásszámot
mér:

| Lépés | Olvasás | Összes repository-számítás |
| --- | --- | ---: |
| első lekérés | miss | 1 |
| azonos második lekérés | hit | 1 |
| invalidálás utáni első lekérés | miss | 2 |
| invalidálás utáni második lekérés | hit | 2 |

Ez helyességi és újraszámítási mérés; latency-javulásra nem tesz állítást.

## Cache-driver eredmények

- `array`: a teljes célzott contract alapértelmezett store-ja.
- `file`: a régi generációs payload fizikailag megmarad, az új kulcs még nem
  létezik, így stale payload nem olvasható.
- `database`: production default és konfigurációaudit; külön driver-specifikus
  célzott futás nem készült.
- Redis/Memcached: nem követelmény és nem használt specifikus viselkedés.

## Negatív igazolások

1. Az anyagfelhasználási invalidálás ideiglenes eltávolításakor a regresszió
   megbukott: tényleges `10.0`, elvárt `6.0`.
2. Ideiglenes `Cache::flush()` esetén az architecture teszt a pontos
   `ProductionTaskMaterialService` fájlt azonosítva megbukott.
3. A rollback tesztekben az üzleti adat és a generation egyaránt változatlan.
4. Minden ideiglenes hibamódosítás visszaállításra került; a végső diff nem
   tartalmazza őket.

## Regressziós gate-ek

| Gate | Eredmény |
| --- | --- |
| `composer validate --strict` | ✅ zöld |
| `vendor/bin/pint --test` | ⚠️ a cache-scope zöld; a teljes aktuális munkafa az idegen `OperationTypeCode.php` `single_quote` eltérése miatt nem zöld |
| `composer analyse` | ✅ zöld, 0 hiba |
| `composer test:cache` | ✅ 20 teszt, 64 assertion, 107,07 s |
| Architecture contract | ✅ 2 teszt, 332 assertion |
| Teljes SQLite backend | ✅ 394 teszt, 1442 assertion, 1994,27 s |
| MySQL backend | ❌ infrastruktúra: `127.0.0.1:33060` visszautasította a kapcsolatot; célzott cache-futás 20 setup-hiba, 0 assertion |
| `npm test` | ✅ izolált futás: 22 fájl, 190 teszt |
| `npm run test:frontend:coverage` | ✅ 22 fájl, 190 teszt; 80,8% statement, 80,69% line |
| `npm run i18n:check` | ✅ 727 szinkronizált kulcs |
| `npm run build` | ✅ 957 modul |
| Playwright Chromium | ❌ 16 passed, 6 failed, 12,6 perc; accessibility/keyboard timeout, document redirect, reservation toast, task state és user-feedback workflow |
| `git diff --check` | ✅ zöld |

Az Xdebug minden PHP-futás elején jelezte, hogy a
`c:/wamp64/logs/xdebug.log` nem írható; ez warning volt, a parancsok működését
nem akadályozta.

## Megmaradt korlátozások

- A Purchase Requisition mutáció konzervatív procurement invalidálása
  hit-rate-et csökkenthet.
- Puszta időmúlásból változó mezők 60 másodperc/5 perc TTL alapján frissülnek.
- Nincs prewarm és terheléses stampede mérés.
- Factory/employee calendarhoz nincs jelenlegi alkalmazási write service; külső
  SQL-módosítás nem része az alkalmazási invalidációs szerződésnek.
- A dedikált MySQL tesztszerver nem hallgat a konfigurált
  `127.0.0.1:33060` címen, ezért MySQL-kompatibilitási assertion nem futott.
- A Chromium teljes gate 6 meglévő, több területet érintő E2E hibával nem zöld;
  a Customer Order, Goods Receipt és Production Plan fő workflow-k átmentek.
- A teljes SQLite suite a később megjelent idegen enum-módosítás előtt futott;
  az enum által közvetlenül érintett Operation Type cache-teszt az aktuális
  munkafán ismét zöld (1 teszt, 1 assertion).

## Auditstátusz

`partially done` — a kód, mátrix, célzott/negatív bizonyítás, teljes SQLite
backend, frontend unit/coverage, i18n és build zöld. A MySQL környezet nem
elérhető, a Chromium gate pedig 6 hibával nem zöld; ezért a feladat a megadott
Definition of Done szerint nem jelölhető `done` állapotúnak.
