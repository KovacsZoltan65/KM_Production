# Rekordállapot-alapú activity log audit

## Cél

Az egyszerű CRUD- és releváns állapotváltási eseményekhez auditható
rekordállapot tartozzon anélkül, hogy a meglévő service-alapú naplózás,
eseménynevek vagy üzleti metaadatok jelentése megváltozna.

## Kiinduló állapot

- A projekt a `spatie/laravel-activitylog` 5.0.0 verzióját használja.
- A csomag alapértelmezett `Spatie\Activitylog\Models\Activity` modellje aktív.
- A saját `activity_log` migráció JSON `attribute_changes` és `properties`
  oszlopot, eventet, lognevet, valamint subject/causer morph kapcsolatokat
  tartalmaz. Batch UUID nincs.
- A modell mindkét JSON oszlopot collectionné castolja.
- 67 kézi audit-hívási hely volt 21 alkalmazásfájlban. A közös
  `AbstractAdminService` és `CodeAwareAdminService` további tíz konkrét admin
  CRUD-service-et fed le.
- Egy modellen sincs `LogsActivity`; auditnapló-megjelenítő UI nincs.

## Elfogadott tervezési döntések

Az automatikus modellnaplózás helyett a service-réteg maradt az audit
tranzakciós és üzleti határa. Így ugyanarra a műveletre nem keletkezik
automatikus és kézi duplikátum.

A Spatie 5 publikus `withChanges()` API-ja közvetlenül az
`attribute_changes` mezőt írja, ezért:

- a rekordállapot és diff az `attribute_changes` mezőben van;
- az üzleti és kapcsolati metaadat a `properties` mezőben marad;
- nem kellett új migráció, egyedi Activity modell vagy belső package API.

A mentés előtti állapothoz `getRawOriginal()`, az új állapothoz
`getAttributes()` adja a mezőkészletet. A naplózott érték mindkét oldalon a
modell castjain át olvasott, majd központilag normalizált érték. Kapcsolatok és
accessorok nem kerülnek be automatikusan.

## Attribútum-adatmodell

Create:

```json
{
  "attribute_changes": {
    "attributes": {
      "id": 15,
      "code": "SUP-0015",
      "is_active": true,
      "name": "Minta beszállító"
    }
  },
  "properties": {}
}
```

Update:

```json
{
  "attribute_changes": {
    "old": {
      "is_active": true,
      "name": "Régi név"
    },
    "attributes": {
      "is_active": false,
      "name": "Új név"
    }
  },
  "properties": {
    "source": "manual"
  }
}
```

Az `old` és `attributes` kulcskészlete azonos és rendezett. Az `updated_at`
nem auditmező; üres attribútumdiff és üres properties esetén nincs update
activity.

## Érzékeny mezők

Központilag tiltottak a jelszó-, hash-, remember token-, API/access/refresh
token-, secret/client secret- és private key mezők, valamint minden encrypted
vagy hashed cast. A névalapú ellenőrzés szó- és aláhúzás-határokat használ,
így például a `secretary_name` nem hamis pozitív.

| Modell | Stratégia | Engedélyezett tartalom |
| --- | --- | --- |
| User | allowlist | azonosító, név, e-mail, ellenőrzési és létrehozási idő |
| Employee | allowlist | munkaviszony- és kapcsolati törzsmezők |
| Customer | allowlist | kód, kapcsolati és számlázási törzsmezők; szabad notes nélkül |
| Supplier | allowlist | kód és kapcsolati törzsmezők; szabad notes nélkül |
| Document | allowlist | üzleti dokumentummetaadat és feldolgozási állapot |

A Document allowlist nem tartalmaz storage pathot, checksumot,
`processing_result`/`processing_error` payloadot, fájlbinárist vagy
kapcsolati gráfot.

## Létrehozási naplózás

Az `AuditLogService::logCreated()` a teljes engedélyezett, perzisztált
rekordállapotot írja. A közös admin create, a kódgeneráló admin create, a
vevőrendelés, BOM, műveletsor, beszerzési igény/rendelés, gyártási terv/feladat,
áruátvétel, stock inbound, quality check és dokumentumfeltöltés ezt használja.

## Módosítási diff és no-op

Az `AuditLogService::logUpdated()` a mentés előtt átadott nyers attribútumokat
egy klónozott modellen ugyanazokkal a castokkal olvassa. A szűrés és
normalizálás után szigorú összehasonlítás készül. Csak eltérő érték kerül az
`old` és `attributes` azonos kulcsaira.

A közös admin update, valamint a vevőrendelés-, BOM-, műveletsor-,
beszerzés-, gyártási-, dokumentum- és releváns AI feldolgozási
állapotváltások ezt használják. No-op admin update nem hoz létre activityt.

## Normalizálás

- `null`, string, int, float és boolean típustartó;
- `BackedEnum` a backing value;
- dátum ISO 8601;
- array/JSON rekurzívan normalizált;
- Laravel vagy PHP `Stringable` string;
- tetszőleges ismeretlen objektum nem kerül naplóba.

## Tranzakciós viselkedés

A közös admin create/update a rekordírást és activity insertet egy
`DB::transaction()` blokkban végzi. A már tranzakciós összetett service-ekben
az audit a meglévő tranzakción belül marad. A szimulált audit insert-hiba
regressziós tesztje igazolja, hogy a közös admin create visszagördül.

## Egyedi service-ek leltára

| Kategória | Service-ek / események | Kezelés |
| --- | --- | --- |
| A – egyszerű create | közös admin, customer order, BOM, operation sequence, procurement, production, goods receipt, quality, document | `logCreated()` |
| B – egyszerű update | közös admin, customer order, BOM, operation sequence, procurement, production, document | `logUpdated()` |
| C – állapotváltás | confirm/cancel/approve/close/start/finish/post, document/AI állapotok | releváns dirty-only diff |
| D – több rekordos tranzakció | generálások, reservation, scheduling, material use | üzleti properties; nincs félrevezető dump |
| E – nem rekordmódosító esemény | login/logout, download, OCR start/result, simulation/classification | visszafelé kompatibilis `log()` |

A törlési események a feladat elsődleges scope-ján kívül maradtak és továbbra
is az általános `log()` API-t használják.

## Kapcsolati változások

- A User szerepkörei create esetén listaként, update esetén régi/új
  kapcsolati diffként a `properties.relations.roles` alatt jelennek meg.
- A BOM itemek, műveletsor-lépések és nested order/plan itemek nem kerülnek
  teljes serializációval a rekorddiffbe. Az üzleti esemény csak a szinkronizálás
  tényét és elemszámát rögzíti.
- Permission-, gyermek- és készletkapcsolatok teljes gráfja nincs naplózva.

## Tesztelés

Új célzott feature-csomag:
`tests/Feature/RecordStateActivityLoggingTest.php`.

Lefedett: create állapot, dirty-only old/new, kulcspár-egyezés, no-op, User
jelszó/hash kizárás, enum/dátum/boolean/null/JSON normalizálás, üzleti
properties elkülönítés és tranzakciós rollback.

Az érintett admin, vevőrendelés, beszerzés, gyártás és dokumentum regressziós
csomag első összevont futása 115 tesztet és 363 assertiont teljesített.

## Quality gate eredmények

- Célzott activity suite SQLite: 3/3 sikeres; futásonként 8 teszt és 31
  assertion.
- Célzott activity suite MySQL: 3/3 sikeres; futásonként 8 teszt és 31
  assertion az izolált `km_production_testing` adatbázison.
- Teljes backend suite SQLite: 3/3 sikeres; futásonként 372 teszt és 1032
  assertion (906,81 s; 963,40 s; 708,83 s).
- Teljes backend suite MySQL: 2/2 sikeres; futásonként 372 teszt és 1032
  assertion. A második futás 1117,60 s volt.
- Érintett service-regresszió: 115 teszt és 363 assertion sikeres.
- Larastan: 0 hiba.
- Pint: sikeres.

A MySQL környezetvédő ellenőrzés a lokális 3306-os WAMP instance-on igazolta
az izolált tesztadatbázist. A projekt alapértelmezett 33060-as Docker portja
nem volt elérhető; ez környezeti előfeltétel-hiány volt, nem teszthiba.
Migráció nem készült, mert a szükséges `attribute_changes` JSON oszlop és cast
már rendelkezésre állt. Frontend fájlt a feladat nem módosított, audit UI
nincs, ezért frontend gate nem volt alkalmazandó.

## Fennmaradó kockázatok

- A régi activity rekordok `attribute_changes` mezője üres, ezért történeti
  megjelenítésnél két struktúrát kell kezelni.
- Audit UI nincs; a diff jelenleg adatbázis/API szintű képesség.
- A törlés előtti rekordállapot külön, későbbi bővítés.
- Retention és személyesadat-megőrzési policy külön governance-feladat.
- Nagy új JSON vagy érzékeny mező bevezetésekor az allowlistet/exclusiont
  kötelező felülvizsgálni.

## Következő lépések

Új érzékeny mezőnél először az `AuditLogService` központi kizárását vagy az
érintett modell allowlistjét kell frissíteni, majd create és update negatív
tesztet kell hozzáadni. Kapcsolati diff csak üzleti indokkal, explicit,
méretkorlátozott properties struktúrában vezethető be.
