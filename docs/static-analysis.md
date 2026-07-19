# Backend statikus elemzés

## Cél és eszközök

A PHPStan a PHP típusszerződéseket ellenőrzi, a Larastan pedig Laravel- és Eloquent-specifikus típusfeloldással egészíti ki. A blokkoló quality gate célja a valódi hibák korai felismerése, nem az elemzés baseline-nal vagy széles elnémításokkal történő zöldre állítása.

A zárolt eszközverziók:

- PHP 8.4;
- Laravel 13.16.1;
- PHPStan 2.2.2;
- Larastan 3.10.0.

## Konfiguráció és scope

A [phpstan.neon.dist](../phpstan.neon.dist) 5-ös szinten elemzi az `app`, `config`, `routes`, `database/factories`, `database/seeders` és `tests` útvonalakat. Csak generált vagy külső könyvtárak vannak kizárva: `bootstrap/cache`, `node_modules`, `public/build`, `storage` és `vendor`.

A projekt nem használ PHPStan baseline-t, `ignoreErrors` szabályt vagy inline `@phpstan-ignore` megjegyzést. Az elemzés nem tölti be a Gitben követett `_ide_helper.php` fájlt, ezért CI-ben nem függ lokálisan generált IDE-helper állománytól vagy adatbázis-kapcsolattól. A folyamat egy elemzőprocesszt és legfeljebb 1 GB memóriát használ; a mért csúcs 426 MB volt 396 fájlnál. A Composer script kikapcsolja a Composer 300 másodperces folyamattúllépését, mert a teljes elemzés ezen a Windows fejlesztői környezeten ennél hosszabb is lehet; a CI job külön 15 perces korláttal rendelkezik.

Helyi futtatás:

```bash
composer analyse
```

A `.github/workflows/backend-quality.yml` ugyanezt a parancsot futtatja PHP 8.4-en, `composer install` és Pint után. A lépés blokkoló, nem használ `continue-on-error` beállítást, és nem generál baseline-t.

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

## Ignore- és helper-szabályok

Inline vagy konfigurációs ignore csak reprodukált, bizonyítható külső csomaghiba esetén maradhat, pontos hibaazonosítóval és magyar indoklással. Saját kódbeli null-kezelési, generic-, array-shape-, argumentum- vagy visszatérési hibát tilos ignore-ral elfedni. Új baseline, általános regex, fájlszintű kizárás és hamis PHPDoc nem használható.

Az `_ide_helper.php`, `_ide_helper_models.php` és `.phpstorm.meta.php` fejlesztői segédfájl. A statikus elemzésnek natív típusokra, PHPDoc-ra és stabil stubokra kell támaszkodnia; új generált helper nem kerülhet automatikusan Git tracking alá, és nem tartalmazhat lokális gépútvonalat.

## Következő szint

A teljes scope 5-ös szinten baseline nélkül zöld. A 6-os szint első mérése 47 hibát adott: hiányzó paginator generics, iterable value típusok és néhány request-/tesztbeli `collect()` template-feloldás alkotja a fennmaradó csoportot. Ez a következő célzott szerződés-audit.

A `phpstan/phpstan-strict-rules` nincs telepítve, ezért strict rules nincs aktiválva; bevezetése külön kompatibilitási mérföldkő. A bleeding edge 5-ös szintű külön mérése 116 hibát adott, főként Pest belső API-jelzéseket és szigorúbb array-shape kompatibilitási hibákat, és a stabil elemzésnél lényegesen lassabban futott. Emiatt nincs aktiválva a CI-ben.
