# Backend statikus elemzés

## Miért kell statikus elemzés?

A statikus elemzés futtatás nélkül keresi a PHP-kódban felismerhető hibákat. Segít észrevenni például, ha egy metódusnak a várttól eltérő típusú adatot adunk, vagy a visszatérési érték nem felel meg a deklarált szerződésnek. A PHPStan típuselemzését a Larastan Laravel- és Eloquent-specifikus típusfeloldással egészíti ki.

Az elemzés nem bizonyítja az üzleti működés helyességét futás közben, az adatbázis viselkedését, a frontend helyességét vagy a teljes felhasználói folyamatokat. Jogosultsági hibából is csak azt észlelheti, ami statikusan felismerhető; a tényleges engedélyezést és tiltást tesztelni kell. A szükséges futási vizsgálatokat a [tesztelési szabályok](../.kiro/steering/testing.md) és a [backendeljárás](backend-quality-gate.md) írják le.

## Szabály: valódi hibát javíts, ne rejts el

Az alkalmazandó elemzésnek a projekt konfigurált szabályaival kell sikerülnie. Saját kódbeli null-kezelési, generic-, tömbalak-, argumentum- vagy visszatérési hibát javíts a tényleges szerződés szerint. A generic a gyűjtemény vagy kapcsolat elemtípusát, a tömbalak a kulcsokat és értéktípusokat teszi egyértelművé.

Ne hozz létre baseline-t (elfogadottnak jelölt hibák listáját), általános hibaelnémítást, hibát elrejtő fájlkizárást vagy hamis PHPDoc-ot a sikeres eredmény kedvéért. Inline vagy konfigurációs kivétel csak reprodukált, bizonyítható külső csomaghibához maradhat, pontos hibaazonosítóval és magyar indoklással. A szűk kompatibilitási kivétel nem ad felmentést saját projekthibákra.

Az elemzés terjedelmét a [rétegezett ellenőrzések](development/quality-gates.md) alapján válaszd ki. Egy célzott elemzés nem bizonyítja a teljes projektet; szélesebb hatásnál a megfelelő nagyobb ellenőrzés szükséges. A készültség és a hiányzó igazolás szabályát a [Definition of Done](project-management/definition-of-done.md) adja.

## Megvalósítás: jelenlegi konfiguráció

A [phpstan.neon.dist](../phpstan.neon.dist) **5-ös szinten** elemzi az `app`, `config`, `routes`, `database/factories`, `database/seeders` és `tests` útvonalakat. A Larastan `vendor/larastan/larastan/extension.neon` kiterjesztését tölti be. Külön `phpstan.neon` nincs a vizsgált munkafában. Kizárt generált, futási vagy külső könyvtárak: `bootstrap/cache`, `node_modules`, `public/build`, `storage` és `vendor`.

A `node_modules` és `public/build` feltételesen létező útvonal, ezért a PHPStan
opcionális exclude jelölését használja. Ez friss, kizárólag backendet telepítő
checkoutban is érvényes konfigurációt ad; nem jelent hibaelnémítást.

A projektkonfiguráció nem használ PHPStan baseline-t vagy `ignoreErrors` szabályt; a vizsgált saját PHP-kódban nincs inline `@phpstan-ignore` megjegyzés. Jelenleg szűk projektszintű hibaelnémítás sincs. Az opcionális útvonalkizárások nem ilyen kivételek.

A konfiguráció nem tölti be a Gitben követett `_ide_helper.php` fájlt. Egy elemzőfolyamatot enged, az átmeneti fájlok helye `storage/framework/cache/phpstan`. A `phpstan/phpstan-strict-rules` nincs a zárolt függőségek között; a projekt nem kapcsol be sem strict rules, sem bleeding edge szabálykészletet. Ezek nem jelenlegi kötelező szabályok; bevezetésük külön feladat.

A [composer.lock](../composer.lock) 2026-09-09-i ellenőrzése szerint Laravel `v13.23.0`, PHPStan `2.2.2` és Larastan `v3.10.0` van zárolva. Ez ellenőrzött repository-állapot, nem elemzési eredmény vagy állandó verziókövetelmény. A PHP futtatókörnyezet nem a lockfile által telepített csomag; a jelenlegi backend CI PHP 8.4-et állít be.

## Eljárás: aktuális parancs

A telepített fejlesztői függőségekkel, a projekt gyökeréből:

```bash
composer analyse
```

A [composer.json](../composer.json) ezt `phpstan analyse --memory-limit=1G --no-progress` paranccsá oldja fel, és kikapcsolja a Composer általános 300 másodperces időkorlátját. Az 1 GB memóriahatár konfiguráció, nem mért fogyasztás.

A [backend workflow](../.github/workflows/backend-quality.yml) ugyanazt a konfigurációt használó elemzést futtatja PHP
8.4-en, `composer install` és Pint után, natív GitHub error formátummal. Ha az
elemző normál hibalista előtt áll le, a rövidített stdout/stderr külön
annotációban is megjelenik. A lépés blokkoló, nem használ `continue-on-error`
beállítást, és nem generál baseline-t. A job külön 15 perces időkorlátot kap.

## Típusszerződés-minták

Eloquent kapcsolat:

```php
/** @return BelongsTo<Customer, $this> */
public function customer(): BelongsTo
```

Collection és stabil tömbalak:

```php
/** @return Collection<int, Item> */

/** @return list<array{id: int, label: non-falsy-string}> */
```

Újrahasznált tömbalaknál a legszűkebb közös scope-ban használható alias:

```php
/** @phpstan-type Option array{id: int|string, label: non-falsy-string} */
```

Request helper csak validált, stabil szerződést adjon vissza:

```php
/** @return array{search?: string|null, sort?: string|null} */
public function filters(): array
```

Repository paginator:

```php
/** @return LengthAwarePaginator<int, ProductionTask> */
public function paginateForExecution(array $filters, int $perPage = 10): LengthAwarePaginator
```

## Fejlesztői segédfájlok

Az `_ide_helper.php`, `_ide_helper_models.php` és `.phpstorm.meta.php` fejlesztői segédfájl. A statikus elemzésnek natív típusokra, PHPDoc-ra és stabil stubokra kell támaszkodnia; új generált helper nem kerülhet automatikusan Git tracking alá, és nem tartalmazhat lokális gépútvonalat.

## Bizonyíték: aktuális futás

Az aktuális eredményhez rögzítsd a dátumot vagy futásazonosítót, a parancsot, az elemzett útvonalakat, a környezetet és a megfigyelt kilépési kódot. Csak tényleges sikeres futás `PASSED`; a talált elemzési hiba `FAILED`. Igazolt környezeti előfeltétel hiánya `BLOCKED`, el nem indított ellenőrzés `NOT RUN`. Az elemző váratlan leállásának okát előbb vizsgáld ki. A hiányzó vagy sikertelen eredményt a DoD szerint, okkal, hatással, felelőssel és következő lépéssel jelentsd.

## Korábbi mérések

Az útmutató korábbi változata az alábbi méréseket őrizte, külön dátum és futásazonosító nélkül:

- 5-ös szint: a teljes akkori hatókör baseline nélkül sikeres volt; 396 fájlnál 426 MB mért memória-csúcs szerepelt.
- 6-os szint első próbája: 47 hiba, főként hiányzó paginator-elemtípusok, bejárható elemek típusai és request-/tesztbeli `collect()` típusfeloldások.
- Bleeding edge, 5-ös szint: 116 hiba, főként Pest belső API-jelzések és szigorúbb tömbalak-kompatibilitási hibák; az akkori stabil futásnál lassabb mérés.

Ezek történeti feljegyzések, nem mai hibaszámok, teljesítményígéretek vagy aktuális sikerigazolások. A korábbi „baseline nélkül zöld” megállapítás nem helyettesíti az új változás ellenőrzését. A [2026-07-27-i projektaudit](audits/project-audit-2026-07-27.md) további dátumozott történeti forrás; az auditok eredeti tartalma megőrzendő.
