# Üzleti cache-stratégia

## Cél és hatókör

Az alkalmazáscache kizárólag drága, olvasási célú aggregátumok gyorsítására szolgál. A cache nem üzleti forrás; a készlet forrása továbbra is a stock movement történet, a többi domain forrása pedig az adatbázisban commitolt állapot.

Az üzleti cache-réteg jelenleg 18 bejegyzést kezel 15 invalidációs domainben:

| Csoport | Bejegyzések | TTL | Kritikusság |
| --- | ---: | ---: | --- |
| Fő dashboard | 1 | 60 másodperc | magas |
| Riportok | 6 | 60 másodperc | magas |
| Manufacturing Intelligence | 7 | 5 perc | magas |
| Kapacitástervezés | 4 | 60 másodperc | kritikus/magas |

Az inventory és procurement operatív listák, selectorok és a lead-time estimator jelenleg nincsenek alkalmazáscache-ben. A `CapacitySlotFinder` tömbjei egyetlen service-példányon belüli memoizációk, ezért nem tartoznak a hosszabb életű cache-invalidációhoz.

## Támogatott driverek

A megoldás a Laravel cache contractjára épül és támogatja az `array`, `file` és `database` store-t. Redis nem követelmény. Cache tageket nem használunk, mert a file és database driverrel nem hordozhatók egységesen. A Prettus repository cache kikapcsolt állapotban marad.

Production alapérték: `database`. Teszt alapérték: `array`. A file driverre külön contract smoke teszt fut.

## Kulcskonvenció

A kulcsokat az `App\Support\Cache\BusinessCacheKey` készíti:

```text
km-production:{domain}:g{generation}:{name}:{parameter-hash}
```

- A domain stabil enumérték.
- A generáció cache-domainenként változik.
- A paraméterek null és üres string értékei kimaradnak, a kulcsok rendezettek.
- A filterhash SHA-256, ezért a paramétersorrend nem változtatja meg a kulcsot.
- Locale, user, factory unit vagy location paramétert minden jövőbeli cache-nek át kell adnia, ha az eredmény ezektől függ.

A generációs megoldás a dinamikus riportfilterek miatt szükséges. Invalidáció után a régi bejegyzések nem olvashatók, majd a saját 60 másodperces vagy 5 perces TTL-jük szerint lejárnak.

## Invalidációs architektúra

Az `App\Services\BusinessCacheInvalidator` üzleti eseménycsaládokra nevezett metódusokat biztosít: customer order, production, inventory, procurement, quality, capacity, workforce és document változás.

Egy metódus csak az esemény adatforrásait használó domain-generációkat lépteti. Nincs `Cache::flush()` és nincs közös `invalidateEverything()` útvonal.

Nyitott adatbázis-tranzakció esetén az invalidáció `DB::afterCommit()` callbackként fut. Rollback esetén nem változik a generáció. Az invalidáció szinkron: a következő request már csak az új generáció kulcsát olvashatja. Queue-zott refresh és automatikus prewarm jelenleg nincs; a cache lazy módon épül újra.

## TTL és stale szabály

| Adat | TTL | Explicit invalidáció | Maximális stale idő write után |
| --- | ---: | --- | ---: |
| Dashboard és riport | 60 s | igen | 0 s |
| Intelligence | 5 perc | igen | 0 s |
| Kapacitás | 60 s | igen | 0 s |
| Spatie permission | 24 óra | package API automatikusan reseteli | 0 s |

A TTL csak hibatűrési és hulladékgyűjtési korlát, nem helyettesíti az üzleti invalidációt.

## Failure viselkedés

A sikertelen generációnövelés `RuntimeException` kivételt okoz; kritikus stale állapotot nem nyelünk el. Failover store csak külön üzemeltetési döntéssel vezethető be.

## Új cache checklist

- [ ] Az adatforrások és üzleti kritikusság dokumentált.
- [ ] A domain és kulcsnév stabil, minden szükséges scope/filter a kulcs része.
- [ ] A TTL indokolt, de nem az egyetlen helyességi védelem.
- [ ] Minden hivatalos write service invalidálja a domaint commit után.
- [ ] Empty-result, hit/miss ekvivalencia és scope-izoláció teszt készült.
- [ ] Array és legalább egy perzisztens driverrel ellenőrzött.
- [ ] A mátrix és a cache-leltár frissült.

## Új write művelet checklist

- [ ] Mely adatbázistáblák és üzleti állapotok változnak?
- [ ] Mely dashboard, riport, intelligence vagy capacity lekérdezés használja őket?
- [ ] Az invalidáció a service-rétegben van és commit után fut?
- [ ] Rollback esetén változatlan marad a cache-generáció?
- [ ] Más scope vagy unrelated domain kulcsa megmarad?
- [ ] Készült pozitív, negatív és empty-result regressziós teszt?
- [ ] SQLite és MySQL gate zöld?

## Ismert korlátok

- A riportok jelenleg globálisak; factory unit vagy location scope bevezetésekor a repository-filtert és a kulcsparamétert együtt kell bővíteni.
- Többszörös eseménynél a generation counter többször léphet. Ez helyes, de csökkentheti a hit rate-et.
- Redis-specifikus stampede/lock viselkedés nem került terheléses tesztelésre.
- Prewarming nincs; invalidáció után az első olvasó számolja újra az aggregátumot.
