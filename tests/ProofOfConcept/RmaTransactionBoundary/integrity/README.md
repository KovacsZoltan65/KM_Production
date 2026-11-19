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
`CanonicalJson` komponenst megvalósítottnak. A1.2 TD01 trust-anchor/raw-digest core az alábbi külön API-ban megvalósult;
a decoded manifest/profile binding és global manifest-ID ledger későbbi kapu.
Native adapter/B2/C2 nincs implementálva. A teljes verifier evaluation/observation szerződését
ez az in-memory validation result nem helyettesíti.

## Focused tests

A repository gyökeréből a standalone A1.1 + TD01 suite:

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

## A1.2 TD01 — pure anchor és trusted digest core

[Elfogadott architektúra](../../../../.kiro/designs/rma-0004-c3-02-9b-trusted-digest-verification-implementation-plan.md),
TD01 külön implementációs engedéllyel. TD02–TD06 nincs implementálva.

- `TrustContext`: immutable, minden constructor-paraméter kötelező:
  `trustMode`, `selectionOrigin`, `selectionRef`, majd az expected exact kilenc
  anchor mező (`anchorVersion`, `anchorId`, `manifestId`, `manifestSha256`,
  `profileId`, `profileSha256`, `approvalRef`, `verifierReleaseId`, `status`).
  A trusted caller ezeket a candidate/manifesttől független recordból adja.
  Origin/ref explicit, nemüres és nem kizárólag whitespace; opaque caller reference,
  nem vezetünk be rá új identifier/ASCII/hosszformátumot. A blank-check nem
  normalizálja vagy írja át a reference-t. `OPERATOR_ATTESTED`-ben selectionRef az independent attestation ref;
  a subsystem az attestation tényleges külső eredetét nem tudja bizonyítani.
- `TrustAnchor`: readonly kilenc stringből álló data-only record; constructor
  önmagában nem érvényességi vagy provenance proof.
- `AnchorProvider(?TrustAnchor)`: előre injektált immutable selected record;
  null explicit missing selection. Nincs keresés, callback, candidate selector,
  environment/CWD/config fallback, store vagy anchor-generálás.
- `AnchorValidator::validate(mixed): TrustAnchor|string`: exact stdClass decoded
  boundary; null `ANCHOR_MISSING`; shape/type/unknown/ID/hash hiba `ANCHOR_INVALID`;
  más string version `ANCHOR_VERSION_UNSUPPORTED`; REVOKED `ANCHOR_REVOKED`.
  Minden mező kötelező; version1.0, ACTIVE, IDs1–80 ASCII `[A-Za-z0-9._-]`,
  digests64 lowercase hex. Raw anchor string reject: nincs strict parsing vagy
  elveszett duplicate-key információból utólagos proof. Ez TD02 feladata.
- `TrustedDigestVerifier(AnchorProvider)::verify(mixed, mixed, string)`:
  explicit context, candidate stdClass, eredeti binary bytes. Sorrend:
  G01 mode/context/runtime → selected record megléte → candidate-validáció →
  selected-record validáció → context/selected/candidate exact tuple-binding →
  profile → verifier release → raw cap → SHA-256 → hash_equals → digest-result.
  Általános tuple-hiba `ANCHOR_SELECTION_MISMATCH`, profile `PROFILE_MISMATCH`,
  release `VERIFIER_RELEASE_MISMATCH`. A digest mezők bindingje is hash_equals.
- `DigestComparisonResult`: immutable, private constructor; phaseVerdict
  MATCH/MISMATCH/DENIED/INCONCLUSIVE, digestStatus MATCH/MISMATCH/`NOT_EVALUATED`,
  nullable primaryFailureCode, bounded nulla/egy statikus stage/path/code/message
  diagnostic. Nincs manifestVerdict vagy Manifest DTO. Korábbi gate failure-nél
  az azonosítók/authenticity/digests null; összevetésnél kizárólag a validált és
  bound selected recordból származó ID/ref/pin és az előállított actual digest.

FIXTURE → authenticity SIMULATED. `OPERATOR_ATTESTED` → `OPERATOR_ATTESTED`,
független operátori kiválasztás és explicit attestation ref mellett.
DEPLOYMENT/`PROTECTED_DEPLOYMENT`/`EXTERNAL_ANCHOR` `CAPABILITY_UNAVAILABLE`,
INCONCLUSIVE, nincs local fallback. Más invalid mode/context `CONFIG_INVALID`.
A caller/verifier/context fölött rendelkező same-user támadó ellen nincs garancia.
ACTIVE nem signature; revocation freshness/global ledger `NOT_EVALUATED`.

**Eredeti raw cap:16777216 bájt**, `strlen` alapján, hash előtt;
túlméret `MANIFEST_SIZE_EXCEEDED`. Nincs truncation, newline/NUL/UTF-8/BOM
normalizálás vagy rewrite. Raw SHA-256 után `hash_equals(expected, actual)`;
mismatch `MANIFEST_UNTRUSTED` hard stop. A digest-phase tudatosan elfogadhat
bármilyen matching raw bytes-t, beleértve nem-JSON vagy üres stringet: ez nem
A1.2 wire-domain acceptance, schema/canonical/decoded manifest binding nincs.
A caller korábbi string-allokációját a pure API nem tudja megakadályozni.

Minden publikus result fix, felül nem írható invariánsai:
`scope=MANIFEST_ONLY`, `fileInventoryStatus=NOT_EVALUATED`,
`filesystemIdentityStatus=NOT_EVALUATED`, `executionAuthorization=NOT_EVALUATED`,
`canLaunchPoC=false`. MATCH nem teljes manifest verification és nem execution
vagy deployment approval. Schema/canonical/decoded-binding státusz `NOT_EVALUATED`.

Külön focused futások ugyanazzal a dependency nélküli runnerrel:

```powershell
php -n tests/ProofOfConcept/RmaTransactionBoundary/integrity/tests/run-tests.php --td01
php -n tests/ProofOfConcept/RmaTransactionBoundary/integrity/tests/run-tests.php --a11
```

A default mindkét csoportot futtatja és külön darabszámot közöl; --td01 nem
require-eli A1.1-et vagy annak schema-ját. A TD01 tesztfájl namespace-local hash
és hash_equals megfigyelői a valódi PHP primitívekhez delegálnak, JSON hívásra
trap működik. A cap+1 esetben tényleges hash call-count0 az assertion;
size/digest boundary inputok synthetic in-memory bytes, nem valid manifestek.
File-wrapper/autoload megfigyelés és token-alapú dependency ellenőrzés egészíti
ki a próbákat; ez nem általános network/OS sandbox. A tesztcélú function shadowing
kizárólag a standalone tesztfájlban van, nincs production hash-injection hook.
PHP programhiba továbbterjed; a runner assertion/exception hibára nonzero-t ad.

Nincs actual anchor/manifest/evidence/fixture output vagy filesystem reader.
A1.1 változatlan. Protected trust/signing/revocation store, strict parser,
canonicalizer, full manifest verifier/result, inventory/native/CLI/integration
későbbi külön authorization. WP7-F01/F02 OPEN; transaction PoC NOT READY.
