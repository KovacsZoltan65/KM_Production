# Fejlesztői cache-útmutató

## Alapelv

A cache gyorsítás, nem üzleti forrás. Készletet kizárólag stock movement és a
kapcsolódó tranzakciós workflow módosíthat. Cache-hiba nem igazol közvetlen
készletmódosítást, teljes cache-flusht vagy a cache eltávolítását.

Az alkalmazás `array`, `file` és `database` store-ral működik. Redis nem
előfeltétel, ezért cache tagek nem használhatók. A Prettus repository cache
`config/repository.php` alatt kikapcsolt.

## Kulcsok

Minden üzleti kulcsot az `App\Support\Cache\BusinessCacheKey` név szerinti
metódusa állít elő. A generált forma:

```text
km-production:{domain}:g{generation}:{name}:{parameter-hash}
```

A paraméterek rekurzívan normalizáltak. Az asszociatív kulcsok és filterlisták
sorrendje nem változtatja meg a kulcsot; a típus, `null`, üres és hiányzó érték
viszont megkülönböztetett. Érzékeny filterérték csak SHA-256 lenyomatban jelenik
meg. Locale, user vagy factory unit kizárólag akkor lehet paraméter, ha az
eredmény ténylegesen ettől függ.

Új cache owner ne hívja közvetlenül a generikus `make()` metódust. Előbb kapjon
beszédes, tesztelhető metódust a központi kulcsosztályban.

## TTL

- Dashboard, riport és capacity: 60 másodperc.
- Manufacturing Intelligence: 5 perc.
- Spatie permission: package-konfiguráció szerint 24 óra, automatikus reset
  mellett.

A TTL hulladékgyűjtési és hibatűrési korlát. Üzleti write után nem helyettesíti
az explicit invalidálást.

## Invalidálás

A `BusinessCacheInvalidator` eseményhatást leíró metódusait a mutációt birtokló
service hívja. A controller, repository és Vue komponens nem invalidál.

```php
$this->cacheInvalidator->inventoryChanged();
$this->cacheInvalidator->suppliersChanged();
```

Dinamikus cache-eknél a megfelelő domain generációja növekszik. Nyitott
tranzakciónál ez commit után történik; rollback esetén a callback nem fut.
Invalidálási hiba nem kerül elnyelésre. A már commitolt adat integritása ilyenkor
megmarad, a request viszont hibával jelzi az üzemeltetési problémát.

## Új cache ellenőrzőlista

- [ ] Drága és ismételt olvasás méréssel vagy query-profillal igazolt.
- [ ] Forrástáblák, fogyasztó, TTL és üzleti kritikusság dokumentált.
- [ ] Új vagy meglévő `BusinessCacheDomain` választása indokolt.
- [ ] Név szerinti `BusinessCacheKey` metódus készült.
- [ ] Minden scope/filter a kulcs része, érzékeny adat csak hashben szerepel.
- [ ] Minden hivatalos write service commit után invalidál.
- [ ] Pozitív eredményfrissülési, rollback- és unrelated-domain teszt készült.
- [ ] Array és legalább egy perzisztens store contract igazolt.
- [ ] A cache-invalidation mátrix és audit frissült.

## Új üzleti esemény ellenőrzőlista

- [ ] Rögzítve van minden módosuló tábla és derived aggregátum.
- [ ] Ellenőrzött a dashboard, report, intelligence és capacity repository.
- [ ] Az invalidálás a service-rétegben van.
- [ ] Nested tranzakció és rollback nem forgat generációt.
- [ ] A teszt régi cache-értéket tölt, write-ot végez, majd új eredményt olvas.
- [ ] Negatív teszt bizonyítja, hogy független domain nem változik.
- [ ] Dokumentált a cache-hiba commit utáni viselkedése.

## Tiltott minták

```php
Cache::flush();
Cache::remember('dashboard', ...);
Cache::forget('copied-string-key');
Cache::tags(['inventory']);
```

A flush független package- és alkalmazáscache-eket is töröl. A nyers string
ütközhet vagy elszakadhat az invalidálástól. A tag nem működik egységesen file,
database és array driverrel. Helyettük név szerinti központi kulcs és
domain-generáció használandó.

## Tesztparancsok

```bash
composer test:cache
php artisan test tests/Unit/Architecture/BusinessCacheArchitectureTest.php
composer quality:backend:sqlite
composer quality:backend:mysql
```

A frontend cache-internals részleteit nem ismeri; frontend teszt csak a
felhasználói eredmény regresszióját védi.
