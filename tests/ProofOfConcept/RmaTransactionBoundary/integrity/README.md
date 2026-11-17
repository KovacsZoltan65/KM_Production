# A1.1 — Manifest schema és pure validation

Standalone PHP 8.4+, 64-bit. Nincs Laravel/vendor bootstrap, dependency,
adatbázis, network, process vagy fájlrendszer-művelet a validálásban.
Az egyetlen entrypoint `ManifestValidator::validate(mixed): ValidationResult`.
A támogatott data contract: `formatId=rma-poc-inputs`, `schemaVersion=1.0`.

```php
require 'src/Manifest.php';
require 'src/ValidationResult.php';
require 'src/ManifestValidator.php';

$result = (new RmaTransactionBoundaryPoc\Integrity\ManifestValidator)->validate($decodedData);
$diagnostics = $result->toArray(); // ['valid' => bool, 'errors' => [...]]
```

Az input JSON-objektumai `stdClass`, inventory-jai PHP listák. Associative array
nem helyettesíti az objektumot: így az üres JSON object és array nem mosódik
össze. A caller például `json_decode($json, false, flags: JSON_THROW_ON_ERROR)`
eredményét adhatja át, de ez **nem** a későbbi strict JSON-parser pótlása.
A validator nem dekódol, nem normalizál és nem módosítja az inputot. Hibás
adat strukturált error; programhiba nincs false-positive sikerre fordítva.

## Field contract

Minden mező kötelező; extra mező minden szinten tiltott. CamelCase:

- Root: `formatId`, `schemaVersion`, `manifestId`, `pocVersion`, `createdAt`,
  `exampleOnly`, `files`, `historicalArtifacts`, `verificationMetadata`.
- `files[]`: `path`, `sizeBytes`, `sha256`, `category`.
- `historicalArtifacts[]`: `path`, `sizeBytes`, `sha256`, `provenanceRef`.
- `verificationMetadata`: `baselineCommit`, `profileId`, `profileSha256`, `provenanceRef`.

Azonosítók ASCII `[A-Za-z0-9._-]{1,80}`; commit40, SHA-25664 lowercase hex.
`exampleOnly` csak `false`: a szemléltető adatot nem fogadjuk input-manifestként.
Mindkét inventory nem üres és ASCII byte-order szerint rendezett; exact duplicate
és case-insensitive collision a két lista között is tiltott. Category:
`poc_code`, `schema`, `toolchain`, `dependency`, `configuration`, `documentation`.
Filenként integer 0–2147483648, együtt maximum8589934592 byte; együttes
inventory limit100000. Deklarált méretek/hash-ek ellenőrzése nem tényleges
file-size/hash mérés. H deployment-kötelezősége későbbi profilfeladat;
a pure validator nem olvassa a történeti evidence-t vagy a repositoryt.

**A1.1-T01:** kizárólag `YYYY-MM-DDTHH:mm:ssZ`, nagybetűs `T`/`Z`, Gregorian
`checkdate` és hour0–23/minute0–59/second0–59. Év0001–9999; törtmásodperc,
timezone offset, leap second és hibás dátum elutasított, automatikus
normalizálás/date-parser/default timezone használata nélkül.

Path: maximum240 ASCII karakter, `/` separator, szegmensenként `[A-Za-z0-9._-]+`.
Nincs dot/dotdot/üres szegmens, trailing dot/space, backslash, drive/UNC/device
namespace, colon/ADS, control/NUL vagy non-ASCII NFC/NFD path. CON/PRN/AUX/NUL,
COM1–9/LPT1–9 extensionnel és nested pathban is tiltott. Unicode normalization
nem kell: a non-ASCII érték fail closed, nincs `intl` dependency. A lexikai
eredmény nem állít semmit valódi linkről, reparse pointról, ADS-ről vagy TOCTOU-ról.

## Result és error contract

`valid`, `errors` és siker esetén immutable `manifest` DTO. A `toArray()` csak
`valid/errors` objektumot ad; nincs runtime timestamp/path/secret és nincs writer.
Minden error: `code`, JSON-szerű `path` (`$.files[0].path`), statikus angol
`message`. A sorrend case-sensitive byte-order: path, code, message.
Az input object property-sorrend nem befolyásolja a diagnosticot. Unknown field
neve csak rövid ASCII identifierként reflektálódik; egyébként `{unknown:N}` a
rendezett unknown kulcsok zero-based pozíciója, hogy secret/path ne szivárogjon.

**R1 diagnostic budget:** globálisan maximum100 error gyűjthető. A 100. error
rögzítése azonnal megszakít minden további validálást egy belső vezérlési
jelzéssel; csak ezt a jelzést kezeli a validator boundary, más exception nem
nyelődik el. Az eredmény `valid=false`, `manifest=null`, és a readonly result
`diagnosticsTruncated=true` értéket kap. Ez a budget miatti korai leállást
jelenti akkor is, ha a bemenet pontosan100 hibát tartalmazna; nem bizonyítja,
hogy biztosan maradt további hiba. Nincs 101. marker error.

A visszaadott legfeljebb100 diagnostic a determinisztikus traversal során
elért részhalmaz, path/code/message szerint rendezve; nem feltétlenül az
összes lehetséges hiba globálisan legkisebb100 eleme. Az error-lista rendezése
legfeljebb100 elemmel dolgozik. A result property alapértéke `false`; a `toArray()`
csak korai leálláskor ad hozzá `diagnosticsTruncated: true` mezőt, így a
küszöb alatti és sikeres serialized result változatlan. A manifest contract
és JSON schema nem változik.

| Code                                                      | Jelentés                                        |
| --------------------------------------------------------- | ----------------------------------------------- |
| `FIELD_REQUIRED`, `FIELD_UNKNOWN`, `TYPE_INVALID`         | Structural contract és coercion nélküli type    |
| `FORMAT_UNSUPPORTED`, `SCHEMA_UNSUPPORTED`                | Nem támogatott format/version                   |
| `IDENTIFIER_INVALID`, `HASH_INVALID`, `TIMESTAMP_INVALID` | Metadata és timestamp lexical/calendar szabály  |
| `EXAMPLE_ONLY`, `CATEGORY_INVALID`, `SIZE_INVALID`        | Tiltott példa/category vagy file-size range     |
| `INVENTORY_EMPTY`, `INVENTORY_ORDER_INVALID`              | Cardinality/order                               |
| `PATH_INVALID`, `DUPLICATE_PATH`, `CASE_COLLISION`        | Lexical és cross-entry path invariáns           |
| `LIMIT_EXCEEDED`                                          | Együttes entry/byte vagy per-list entry maximum |

## JSON schema és PHP különbségek

A [schema](schema/input-manifest-v1.schema.json) Draft2020-12 szókészlettel
írja le a contractot; **JSON Schema validation engine nem fut és compliance-t
nem állítunk**. A tesztek schema syntaxot/required/property/category/bounds
és lexical regex parityt vizsgálnak PCRE-vel, nem teljes schema-evaluationt.

PHP-val kiegészített szigorúbb ellenőrzések: Gregorian month/leap-year szabály,
ordered paths, path szerinti duplicate különböző entry-tartalommal is,
cross-inventory collision/overlap, combined100000 és total8GiB. JSON Schema
`integer` matematikailag egész floatot is elfogadhat; PHP kifejezetten integer
runtime értéket kér, `1.0`, string, bool nem coercionálódik. `uniqueItems` a
schema-ban csak teljesen egyforma objektumokat képes elutasítani.

A1.1 nem értékeli a nyers JSON byte-limit/depth/BOM/duplicate-object-key/
canonical-key-order/final-LF szabályokat: már dekódolt adaton ezek részben
elvesznek. A szigorú parser/canonicalizer külön későbbi pure pre-validation
réteg, digest-ellenőrzés utáni parse-sorrenddel. Nem állítjuk a teljes tervezett
`CanonicalJson` komponenst megvalósítottnak. A1.2 trust anchor, eredet/digest,
manifest-ID reuse és profile compatibility külön kapu; native adapter/B2/C2
nincs implementálva. A teljes verifier evaluation/observation szerződését
ez az in-memory validation result nem helyettesíti.

## Focused tests

A repository gyökeréből kizárólag az új standalone suite:

```powershell
php -n tests/ProofOfConcept/RmaTransactionBoundary/integrity/tests/run-tests.php
```

A runner csak saját source-ait és schema-ját olvassa, JSON-payloadokat memóriában
épít; nincs actual executable manifest, fixture directory, evidence output,
Laravel/Pest/application bootstrap vagy PoC/PDO/SQL. PHP warning is teszthiba.
Az I/O-tilalmat a pure validator hívása körül file-wrapper trap vizsgálja;
scope-ellenőrzés és input-immutability teszt egészíti ki. Ez nem malicious
code elleni sandbox vagy natív filesystem proof. A root application suite,
Composer setup/test, L/R/P/W nem e teszt parancsai.

**Successful schema validation does not establish manifest authenticity, file
integrity, filesystem safety, or authorization to execute the transaction PoC.**

WP7-F01 és WP7-F02 OPEN. [Design és scope](../../../../.kiro/designs/rma-0004-c3-02-9a-manifest-integrity-implementation-plan.md),
[timestamp decision](../../../../.kiro/designs/rma-0004-c3-02-8-execution-architecture-specification.md).
