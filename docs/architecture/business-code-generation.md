# Automatikus üzleti kódgenerálás

## Hatókör és felderítési leltár

A projekt üzleti azonosítóinak felderítése a modellekre, enumokra, migrációkra,
Requestekre, policy-kre, service-ekre, repository-kra, seederekre, factory-kra,
admin modalokra és tesztekre terjedt ki.

| Entitás | Modell / tábla / mező | DB-típus | Unique | Soft delete | Jelenlegi jelleg | Közös generátor |
| --- | --- | --- | --- | --- | --- | --- |
| Gyártóegység | `FactoryUnit` / `factory_units.code` | `varchar(255)`, Request max. 50 | igen | igen | kézi törzsadatkód | `FU` |
| Dolgozó | `Employee` / `employees.employee_number` | `varchar(255)` | igen | igen | kézi törzsadatkód | `EMP` |
| Hely | `Location` / `locations.code` | `varchar(255)`, Request max. 50 | igen | igen | kézi törzsadatkód | `LOC` |
| Szakmai szerepkör | `ProfessionalRole` / `professional_roles.code` | `varchar(255)`, Request max. 50 | igen | igen | kézi törzsadatkód | `ROLE` |
| Cikk | `Item` / `items.item_number` | `varchar(255)` | igen | igen | kézi törzsadatkód | `MAT` vagy `PRD` |
| Vevő | `Customer` / `customers.code` | `varchar(255)` | igen | igen | kézi törzsadatkód | `CUST` |
| Beszállító | `Supplier` / `suppliers.code` | `varchar(255)` | igen | igen | kézi törzsadatkód | `SUP` |
| Művelettípus | `OperationType` / `operation_types.code` | `varchar(255)` | igen | igen | `OperationTypeCode` stabil enumérték | nem |
| Vevői rendelés | `CustomerOrder.order_number` | `varchar(255)` | igen | igen | workflow-generátor | nem |
| Gyártási terv | `ProductionPlan.plan_number` | `varchar(255)` | igen | igen | workflow-generátor | nem |
| Gyártási rendelés | `ProductionOrder.order_number` | `varchar(255)` | igen | igen | workflow-generátor | nem |
| Beszerzési igény | `PurchaseRequisition.requisition_number` | `varchar(255)` | igen | igen | workflow-generátor | nem |
| Beszerzési rendelés | `PurchaseOrder.order_number` | `varchar(255)` | igen | igen | workflow-generátor | nem |
| Áruátvétel | `GoodsReceipt.receipt_number` | `varchar(255)` | igen | igen | workflow-generátor | nem |
| Cikkpéldány | `ItemInstance.serial_number` | `varchar(255)` | igen | nem | nyomonkövetési sorozatszám-generátor | nem |

A művelettípus kódja nem sorszámozott azonosító: a validáció és a model cast az
olyan stabil szemantikai enumértékekre épül, mint a `CUTTING` és a `WELDING`.
Az `OP-0001` bevezetése ezért üzleti szerződést törne. Az `OP` prefix
konfigurációja az előírt előkészítés része, de jelenleg nincs publikus
`operation_type` generálási definíció.

A rendelési, gyártási, beszerzési, áruátvételi és sorozatszámmezők saját
workflow-, dátum-, gyártóegység- vagy tranzakciófüggő generátorral rendelkeznek.
Ezeket a törzsadatkód-generátorba bevonni hibás felelősség-összemosás lenne.

## Architektúra

- A `CodeDefinitionRegistry` a publikus típust modellre, táblára, mezőre és
  prefixkulcsra fordítja. A kliens nem adhat át modell- vagy oszlopnevet.
- A `CodePrefixResolver` a konfigurált prefixet, majd biztonságos beépített
  alapértéket használja. A későbbi adatbázis-provider ezen a ponton illeszthető
  be; a `CodeGeneratorService` módosítása nem szükséges.
- A `CodeSequenceRepository` a soft-deleted rekordokat is olvassa, és csak az
  adott prefixszel kezdődő értékeket adja a generátornak.
- A `CodeGeneratorService` escaped regexszel értelmezi a numerikus részt, a
  legnagyobb érvényes értéket eggyel növeli, majd balról nullázza.
- A `CodeCreationService` kezeli a mentéskori unique ütközést. Csak a definíció
  célzott kódindexének hibáját kezeli; más adatbázishibát változatlanul továbbad.
- A `CodeAwareAdminService` egységesen kapcsolja a létrehozást az admin
  service/repository/audit folyamathoz.
- A `GET /admin/code-generation/{type}` végpont csak javaslatot ad, nem foglal
  sorszámot és nem módosít adatot. Az adott modell policy-jének `create`
  jogosultságát követeli meg.

## Formátum és számsor

Az alapformátum `PREFIX-0001`. Az érvényes régi értékek numerikus része
tetszőleges hosszúságú lehet, például a `SUP-12` is érvényes. A `SUP-A12`,
`OLD-SUP-0008` és más prefixű érték nem számít bele. Üres számsor `0001`-ről
indul. A soft-deleted kódok beleszámítanak, így üzleti azonosító nem használható
fel újra.

A cikktípusok feloldása:

- `purchased_material` → `MAT`;
- `manufactured_part`, `semi_finished_product`, `finished_product` → `PRD`.

A MAT és PRD prefix külön számsort jelent.

## Konfiguráció

Az alkalmazáskód kizárólag a `config/code_generation.php` fájlt olvassa.

```dotenv
CODE_PREFIX_FACTORY_UNIT=FU
CODE_PREFIX_EMPLOYEE=EMP
CODE_PREFIX_LOCATION=LOC
CODE_PREFIX_PROFESSIONAL_ROLE=ROLE
CODE_PREFIX_PRODUCT=PRD
CODE_PREFIX_MATERIAL=MAT
CODE_PREFIX_OPERATION_TYPE=OP
CODE_PREFIX_CUSTOMER=CUST
CODE_PREFIX_SUPPLIER=SUP
CODE_SEQUENCE_LENGTH=4
```

A konfiguráció cache-elhető; környezeti érték módosítása után a szokásos Laravel
config-cache folyamatot kell alkalmazni.

## Create és Edit működés

A támogatott Create modalokban a kód kötelező és kézzel szerkeszthető. A
`Generálás` gomb aszinkron javaslatot kér, loading alatt letiltott, és nem indít
párhuzamos dupla kérést. A cikk modal az aktuális `item_type` értéket is elküldi.
A modal megnyitása önmagában nem generál és nem foglal kódot.

Edit módban az azonosító látható, de disabled. A közös frontend kihagyja az
update payloadból. A backend csak a már tárolt értékkel azonos kompatibilitási
értéket fogadja el; eltérő közvetlen HTTP-payload validációs hibát kap.

## Ütközéskezelés

A technikai `_code_was_generated` jelző nem biztonsági döntéshez, kizárólag a
felhasználói folyamathoz használható, és nem kerül adatbázisba.

- Változatlan generált kód ütközésekor a backend új javaslatot képez és legfeljebb
  három mentési kísérletet végez. Siker esetén a lokalizált success üzenet az
  eredeti és a tényleges kódot is tartalmazza.
- Kézi vagy generálás után átírt kód ütközésekor nincs automatikus mentés. A
  validációs válasz `code_suggestion` mezőjét a modal beírja a kódmezőbe, és a
  felhasználónak ismét mentenie kell.
- A végső védelem minden támogatott mező meglévő adatbázis unique indexe.

## Új kódtípus hozzáadása

1. Ellenőrizni kell, hogy valóban kézi törzsadat-azonosítóról, nem enum- vagy
   workflow-kulcsról van szó.
2. Prefixet és biztonságos fallbacket kell felvenni a configba, `.env.example`
   fájlba és a resolverbe.
3. Definíciót kell adni a registryben, valódi modell-, tábla-, mező- és
   soft-delete adatokkal.
4. Az admin service-t a `CodeAwareAdminService` osztályhoz kell kapcsolni.
5. A Store Requestnek engednie kell a technikai boolean jelzőt; az Update
   Requestnek változtathatatlanná kell tennie a kódot.
6. A CRUD mezőn `generateCode` és `immutableOnEdit` konfiguráció szükséges.
7. Registry-, generátor-, jogosultság-, collision- és modaltesztet kell írni.

## Későbbi integrációs pont

A következő ütem a céges Beállítások oldal és az adatbázisban tárolt prefixek
bevezetése. Az adatbázis-providernek a `CodePrefixResolver` feloldási sorrendje
elé kell kerülnie: céges beállítás → config → biztonságos fallback.
