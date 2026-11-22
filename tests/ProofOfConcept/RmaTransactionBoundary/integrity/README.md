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
eredményét adhatja át, de ez **nem** a megvalósított TD02 restricted-domain Strict JSON Parser pótlása.
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
elvesznek. A szigorú parser TD02-ben, a pure `CanonicalJson` komponens TD03-ban
elkészült. A digest-ellenőrzés utáni parse és az A1.1 utáni canonical comparison
összekötése TD04-ben elkészült (külön alább). A1.2 TD01 trust-anchor/raw-digest core az alábbi külön API-ban megvalósult;
A decoded manifest/profile binding TD04-ben elkészült; a global manifest-ID ledger későbbi kapu.
Native adapter/B2/C2 nincs implementálva. A teljes verifier evaluation/observation szerződését
ez az in-memory validation result nem helyettesíti.

## Focused tests

A repository gyökeréből a standalone A1.1 + TD01 + TD02 + TD03 + TD04 suite:

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
TD01–TD04 külön implementációs engedéllyel elkészült. TD05–TD06 nincs implementálva.

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

A default az A1.1, TD01, TD02, TD03 és TD04 csoportot futtatja és külön darabszámot közöl; --td01 nem
require-eli A1.1-et vagy annak schema-ját. A TD01 tesztfájl namespace-local hash
és hash_equals megfigyelői a valódi PHP primitívekhez delegálnak, JSON hívásra
trap működik. A cap+1 esetben tényleges hash call-count0 az assertion;
size/digest boundary inputok synthetic in-memory bytes, nem valid manifestek.
File-wrapper/autoload megfigyelés és token-alapú dependency ellenőrzés egészíti
ki a próbákat; ez nem általános network/OS sandbox. A tesztcélú function shadowing
kizárólag a standalone tesztfájlban van, nincs production hash-injection hook.
PHP programhiba továbbterjed; a runner assertion/exception hibára nonzero-t ad.

Nincs actual anchor/manifest/evidence/fixture output vagy filesystem reader.
A1.1 változatlan; a restricted-domain strict parser TD02-ben, a canonicalizer TD03-ban elkészült.
Protected trust/signing/revocation store,
teljes A1 file/native verifier/result, inventory/native/CLI/integration
későbbi külön authorization. WP7-F01/F02 OPEN; transaction PoC NOT READY.

## A1.2 TD02 — restricted strict JSON parser

TD02 külön implementációs engedéllyel készült; TD03 canonicalizer és TD04 orchestration külön alább, TD05–TD06 nincs implementálva.
Az elfogadott A1.2 terv és TD02-D01–D07 alapján standalone PHP 8.4+, 64-bit API:
`StrictJsonTokenizer::parse(string $rawBytes): JsonParseResult`.

`JsonParseResult` final readonly, privát konstruktorral. Siker: `valid=true`,
teljes `value` (akár JSON null), `error=null`. Inputhiba: `valid=false`,
`value=null`, egy `code/offset/message` error. Nincs partial graph vagy hibalista.
Runtime/programhiba továbbterjed; kizárólag saját input-failure signal fogható el.
Nem támogatott integer width fix `RuntimeException`, parse előtt; PHP 8.4 alatti
runtime szintén kivétel. A graph `stdClass` része nem mélyen immutable.

Konstrukció: `parse(raw)` → `JsonParseResult::fromParsing(final parser, raw)` →
`evaluate(raw)` egyszer → privát resultkonstrukció. Az evaluate adatai nem
fogadhatók el publikus success factory bemeneteként; nincs `success(value)`,
`fromArray` vagy hydration API. A `__unserialize()` mindig kivétel. Ez nem
arbitrary same-process PHP/Reflection elleni garancia és nem authenticity proof.

A parser explicit stackkel, eredeti binary stringen, normalization/repair,
`json_decode`, float fallback és canonicalization nélkül dolgozik. Exact
stdClass/list/int/string/bool/null reprezentáció. Objectenként külön, prefixed
seen-set ellenőrzi a decoded, case-sensitive kulcsokat overwrite előtt.
Escaped-ekvivalens kulcs duplicate; más objectben azonos kulcs megengedett.

UTF-8/BOM/surrogate hiba `ENCODING_INVALID`; helyes non-ASCII decoded karakter
`JSON_NON_ASCII`. Grammar/escape/raw control hiba `JSON_INVALID`. Decoded key
csak printable ASCII (`JSON_KEY_INVALID`); escaped ASCII control értékben
megengedett, a key-szabály nem terjed át minden stringre. Field/path/timestamp
szabályok továbbra A1.1-ben maradnak.

Szám: csak `0` vagy `[1-9][0-9]*`, maximum `9223372036854775807`, lexikai
overflow guard cast előtt. JSON-valid negatív/fraction/exponent forma
`JSON_NUMBER_INVALID`; overflow `JSON_INTEGER_OVERFLOW`. Hibás grammar vagy
tokenhatár előbb `JSON_INVALID`; hosszú numeric token nem kap teljes másolatot.

| Resource                          |       Maximum |
| --------------------------------- | ------------: |
| Eredeti raw input                 | 16777216 byte |
| Container depth, root=1           |            16 |
| Decoded value / key               | 240 / 20 byte |
| Member egy objectben              |             9 |
| Globális array-element            |        100000 |
| Globális container                |        100004 |
| Globális scalar value, key nélkül |        400010 |
| Globális object member            |        400013 |

Guard a túllépő append/push/insertion előtt; állapot és globális számlálók
hívásonként frissek. Depth: `JSON_DEPTH_EXCEEDED`; raw size:
`MANIFEST_SIZE_EXCEEDED`; más resource: `JSON_COMPLEXITY_LIMIT`.
Ezek strukturális korlátok, nem bizonyított PHP-memory igények.

Offset 0-alapú raw byte-index; EOF=strlen(raw). UTF-8/escape: szekvencia vagy
backslash kezdete; duplicate: második key nyitó quote; tiltott number/overflow:
tokenkezdő; depth: tiltott opening token; decoded hossz: első túlcsorduló egység
raw kezdete; member/element: következő tiltott elem kezdete; trailing data:
első nem-whitespace; raw oversize:16777216. Precedence: runtime → raw size →
balról jobbra scan/pre-allocation guards → grammar/encoding → token-domain →
duplicate/number minősítés → root utáni whitespace. Az előbb aktiválódó
resource guard megelőzhet későbbi tokenhibát. Diagnosztika statikus, nincs raw
fragment, arbitrary key vagy abszolút path.

A tesztek a globális limiteket is elérik általános restricted JSON graphokkal:
100000 elemű root listában 100000 object, alapból négy member/object;
az első három object member-száma 9/9/7 és három value üres array. Így
E=100000, C=100004, S=400010, M=400013. A további member/scalar/container
külön túllépési tesztet kap; nincs production-limit gyengítés. A valid manifest
graphja és a general restricted parser-boundary graph külön tesztkategória.

Futtatás repository gyökérből, alkalmazás/bootstrap nélkül:

```powershell
php -n tests/ProofOfConcept/RmaTransactionBoundary/integrity/tests/run-tests.php --td02
php -n tests/ProofOfConcept/RmaTransactionBoundary/integrity/tests/run-tests.php --a11
php -n tests/ProofOfConcept/RmaTransactionBoundary/integrity/tests/run-tests.php --td01
php -n tests/ProofOfConcept/RmaTransactionBoundary/integrity/tests/run-tests.php --all
php -n tests/ProofOfConcept/RmaTransactionBoundary/integrity/tests/run-tests.php --td02-memory
```

Default: mind az öt csoport, explicit suite selection. Exit0 success, exit1
teszthiba, exit2 unsupported runtime, exit64 usage. Opcionális, már telepített
Xdebug develop módban valódi function monitor ellenőrzi mindkét parserbelépés
egyszeri evaluation/scan hívását, oversize-nál scan0-t; A1.1 ensure és
expected-error assertion-száma is megfigyelhető a kódjának módosítása nélkül.
Xdebug nélkül ezek a megfigyelő esetek nem regisztrálódnak; a normál suite
nem állítja, hogy monitorbizonyítékot szolgáltatott.

Memóriaharness: hat synthetic in-memory scenario, mindegyik három külön
`PHP_BINARY -n -d memory_limit=512M` subprocessben, 30s/scenario timeouttal.
Az allowlistelt belső `--td02-memory-case=ID` mód pipe-on ad egy JSON rekordot:
PHP/verzió/width/limit, scenario, raw size, peak used/allocated, parser-result.
A parent exit/timeout/stderr/ismételhetőség alapján dönt; fatal/OOM/missing
record nem elfogadható failure. Nincs shell, fixture/output/cache file.
Az 512MiB tesztbudget, nem általános memóriaígéret; a limitek nem emelhetők
automatikusan. Peak a fixture építését és valid esetben az élő parsed graphon
végzett változatlan A1.1 validációt is tartalmazza, nem csak a parser saját
allokációját és nem teljes OS RSS-t mér.

`wire-max`: a terv WD03 receptje, pontosan16777216 byte. `byte-heavy`:
40000 file +1 history, 240-byte pathok. `object-heavy`:1 file +99999 history;
`array-heavy`:99999 file +1 history, rövid unique sorted pathok.
Ezek kézzel rögzített synthetic JSON-receptek, nem TD03 serializer.
`invalid-stress`:16MiB integer token → overflow; `defensive-limit`:16MiB
quoted ASCII value → decoded hosszlimit. A mérési eredmények a feladat
átadási jelentésében szerepelnek, nem történeti evidence átírásával.

Nincs TD01 módosítás, anchor acquisition/selection/provenance, új anchor16KiB
cap, digest/calculated pin, canonical equality, orchestration vagy execution
authorization. WP7-F01/F02 OPEN; transaction PoC NOT READY.

## A1.2 TD03 — pure canonical JSON

`CanonicalJson::serialize(Manifest $validatedManifest): string` és
`CanonicalJson::isCanonical(Manifest $validatedManifest, string $rawBytes): bool`.
Final, stateless C08; PHP 8.4+, 64-bit. A caller kötelező preconditionje a sikeres
A1.1-validációból származó immutable Manifest snapshot. A publikus Manifest típus
nem validációs bizonyíték; TD03 nem hív validátort, nem fogad mutable TD02 gráfot,
nem vezet be capabilityt vagy provenance tokent. A representation/type guardok
nem ismétlik meg az A1.1 schema/path szabályait. D01=A ezt a trusted-caller
szerződést rögzíti; tetszőleges kézzel konstruált DTO-ra nincs schema-garancia.

Exact v1: minden object ascending ASCII key-order, listák eredeti sorrendben;
quote/backslash escaped, slash literal, rövid b/t/n/f/r escape-ek, egyéb control
lowercase `\u00xx`, printable ASCII literal. Nincs Unicode-normalizálás vagy
float coercion; integer plain decimal, boolean lowercase. Pontosan egy final LF,
BOM/indent/CRLF nélkül. Az izolált escape-helper tesztjei nem bővítik a valid
manifest-domain-t. Unsupported representation fix kivételt ad, nincs partial
siker; más programhiba továbbterjed.

Az összehasonlítás length + exact byte equality, trim/reparse/hash/repair nélkül.
A caller ugyanazt az eredeti raw snapshotot adja, amelyet TD01 és TD02 használt;
TD03 ezt az eredetet nem bizonyítja. A false TD04 mappingje
`MANIFEST_NONCANONICAL`; a TD03 maga nem orchestrator. Canonical equality nem digest,
schema, authenticity, file inventory, filesystem identity vagy execution proof.

A fixed-shape serializer bounded appendekkel dolgozik, nem általános recursive
JSON encoder. Eredeti raw cap16777216; ez nem canonical-output cap. A teljes
A1.1-valid canonical domain levezetett maximuma44584955 bájt final LF-fel.
Ennek guardja nem runtime memóriaigazolás. A teljes maximum manifestet nem
futtatjuk a representative wire-domain mérések helyett.

```powershell
php -n tests/ProofOfConcept/RmaTransactionBoundary/integrity/tests/run-tests.php --td03
php -n tests/ProofOfConcept/RmaTransactionBoundary/integrity/tests/run-tests.php --all
php -n tests/ProofOfConcept/RmaTransactionBoundary/integrity/tests/run-tests.php --td03-memory
```

Default és `--all`: A1.1, TD01/R01, TD02, TD03, TD04. A `--td03` csak saját suite-ot
futtat, a parser és A1.1 input-előkészítési függőségeivel, TD01 nélkül.
Golden expected byte-literalok, hardcoded byte count és SHA-256 oracles külön
Python standard-library reference-ből készültek; nem a tesztelt serializerből.
GB01 small, GB02 multi-entry, GB03 string/size boundary, GB04 private escape-table,
GB05 leap-day; a korábbi gate-ek hibái nem canonical mismatchként szerepelnek.

D02=A memóriaacceptance: hat saját scenario (small, multi, ascii-heavy,
inventory-heavy, wire-max, noncanonical-large), mindegyik három külön
`PHP_BINARY -n -d memory_limit=512M` childban, processzindítás előtti monoton
kezdőponttal, 30s deadline-nal és validáció utáni friss acceptance-ellenőrzéssel.
A parent exit/stderr/complete metrics/expected byte count+SHA/equality és
ismételhetőség alapján dönt; fatal/OOM/timeout/hiányos rekord nem PASS.
Peak used/allocated a fixture, TD02/A1.1 előkészítés, serializer, comparison és
immutability megfigyelés terhelését is tartalmazza; nem teljes OS RSS.
512MiB mérési budget, nem általános memóriaígéret. Nincs automatikus emelés,
shell, fixture/evidence output vagy repository-artifact.

TD05–TD06 nincs implementálva; TD01/TD02/A1.1 változatlan. A TD04 manifest-only
orchestration külön alább; nincs acquisition, canonical rewrite vagy launch authorization.
WP7-F01/F02 OPEN; transaction PoC NOT READY.

## A1.2 TD04 — manifest-only verification orchestration

`new ManifestDigestVerifier(AnchorProvider $anchors)` és
`verify(mixed $context, mixed $candidateAnchor, string $rawBytes): ManifestVerificationResult`.
A fixed provider és az immutable context independent selectionje változatlan;
decoded candidate a TD01 boundaryja. Raw anchor stringet nem decode-olunk
hallgatólagosan, raw delivery strict parsingja külön caller/delivery feladat.

G01 runtime/context/mode → G02 selected/candidate anchor/profile/release binding
→ G03 original raw cap → G04 TD01 SHA-256 comparison → G05 TD02 strict parse
→ G06 egy A1.1 validate → G07 egy TD03 isCanonical → G08 decoded manifest/profile
binding → G09 immutable manifest-only result. G01 PHP 8.4+, 64-bit és SHA-256
capability preflight; G01–G04 security döntéseit a meglévő TD01 végzi.
Az első hibánál stop: későbbi státuszok NOT_EVALUATED. A schema/path hiba
megelőzi a noncanonical hibát, az pedig a decoded binding hibáját.

Ugyanaz az eredeti binary string megy a digest/parser/comparison API-knak;
nincs normalizálás, második acquisition, pinjavítás vagy külön serialize hívás.
Csak az A1.1 sikeres immutable snapshotja jut TD03-hoz. A parser mutable gráfja
nem validációs bizonyíték és nem kerül a publikus resultba.

`ManifestVerificationResult` final readonly, private konstruktorral.
A `fromVerification(final verifier, context, candidate, raw)` factory ténylegesen
futtatja az orchestrationt; nincs caller-supplied success/facts factory.
Az internal evaluate facts nem visszatölthető capability. Unserialization tiltott;
ez nem arbitrary Reflection/same-process kódfuttatás elleni sandbox.
`manifestVerdict` az architecture mező, `verdict` annak azonos readonly aliasa:
VERIFIED/DENIED/INCONCLUSIVE csak MANIFEST_ONLY scope-ra. Nincs teljes inputVerdict.
Failure esetén manifest=null, success esetén a tényleges A1.1 DTO érhető el.

Minden resultban fix: scope=MANIFEST_ONLY, fileInventoryStatus=NOT_EVALUATED,
filesystemIdentityStatus=NOT_EVALUATED, executionAuthorization=NOT_EVALUATED,
canLaunchPoC=false; anchorHistoryStatus=NOT_EVALUATED. Nincs raw payload,
mutable parsed gráf, runtime timestamp vagy OS error a resultban.
Canonical equality önmagában nem authenticity/provenance vagy execution approval.

G06 primaryFailureCode=MANIFEST_SCHEMA_INVALID, az eredeti legfeljebb100 A1.1
code/path/message változatlan, stage=V06_V07, pontos diagnosticsTruncated átvitel.
Nincs101. summary error. Más gate egy statikus diagnosticot ad. Parser code/message
megmarad; a wrapper `$` pathot használ, külön parser-offset mezőt nem exportál.
Programhiba exception továbbterjed; INTERNAL_ERROR mapping későbbi külső boundary.

Raw cap16777216, TD02 depth16/value240/key20/per-object9 és globális
element100000/container100004/scalar400010/member400013 változatlan.
A1.1 inventory100000/file2GiB/total8GiB/diagnostics100 és TD03 output44584955
változatlan. Raw cap nem canonical-output cap. A wrapper nem tart request-state-et;
a parsergráfot validálás után elengedi, a canonical outputot csak isCanonical építi.

```powershell
php -n -d memory_limit=512M tests/ProofOfConcept/RmaTransactionBoundary/integrity/tests/run-tests.php --td04
php -n -d memory_limit=512M tests/ProofOfConcept/RmaTransactionBoundary/integrity/tests/run-tests.php --all
php -n tests/ProofOfConcept/RmaTransactionBoundary/integrity/tests/run-tests.php --td04-memory
php -n tests/ProofOfConcept/RmaTransactionBoundary/integrity/tests/run-tests.php --td04-r1
```

Default/--all öt csoport; --td04 csak saját cases-t futtat. A TD01 tesztfájl
observer-függvényeit setupként használja, nem deklarálja újra őket. A TD04
test-only preg_match observer alapból a PHP primitívhez delegál. Lifecycle
fault-injection csak A1.1 regexhívásban okoz controlled failure-t: ugyanazon
verifier/provider/pin/raw mellett valid→truncated→valid→below-budget→valid.
Ez szintetikus validator fault, nem több eltérő raw input elfogadása egy fix pinhez.

`--td04-observe` külön Xdebug develop módban futtatandó, a telepített zend_extension
explicit betöltésével. Actual parse/validate/isCanonical/serialize call-countot
közöl; Xdebug hiányában nem állít megfigyelési PASS-t. A memóriafutások `-n`
childjai külön vannak a megfigyeléstől, nem használnak Xdebugot.

TD04-D01=A: small/multi/byte-heavy/entry-heavy/wire-max/noncanonical-large,
mindegyik3 külön512M gyermek,30s deadline a proc_open előtti monoton kezdőponttól
a rekordvalidáció utáni friss ellenőrzésig. Actual size/digest/result/peak used és
allocated, exit/stderr és ismételhetőség vizsgált; első failure-nél megáll.
Nincs automatikus retry/budget-emelés. Külön R1:99999 üres file-object +1 üres
history-object,128M child;100 diagnostic, truncated=true, manifest=null szükséges.
Fixture byte count és digest pinned reference, nem tesztelt serializer output.
Peak a fixture/context/TD02/A1.1/TD03/result teljes terhelését tartalmazza,
nem OS RSS és nem a teljes44584955-bájtos decoded-domain runtime bizonyítása.

Szerzői ellenőrzés (2026-11-21, PHP8.4.27,64-bit): TD04 56/56,801 assertions,
69 result scope-ellenőrzés; A1.1 203/203, TD01/R01 226/226 (7033 assertions),
TD02 115/115 (4612), TD03 43/43 (238), combined --all 643/643,0 failed.
A combined runner négy assertion-számlálójának összege12684; az A1.1 ebben a
`-n` futásban nem ad assertion-számlálót, így ez nem az öt suite teljes assertion-száma.
A négy módosított/új PHP syntax check, célzott Pint és README Prettier sikeres.
Nem futott Laravel/SQL/VM/native teszt: standalone pure scope, nincs ilyen változás.

Külön Xdebug3.5.3 develop megfigyelés, --td04-observe (parse/validate/isCanonical/serialize):
digest mismatch0/0/0/0, parser failure1/0/0/0, schema failure1/1/0/0,
success1/1/1/1. Ez actual method monitor, nem forrásszövegből számolt hívásszám.
További stdin-only preflight negatívok: valódi PHP8.3.28, illetve PHP8.4.27 alatt
külön letiltott hash vagy hash_equals: mind CAPABILITY_UNAVAILABLE/INCONCLUSIVE,
digest NOT_EVALUATED, manifest=null. Külön32-bit binary próba nem futott.

TD04-D01 actual18/18 PASS, mind512M, exit0,stderr üres,timeout=false:

| Scenario           | Raw bytes | Három wall-clock futás (s) | Peak used (byte) | Peak allocated (byte) |
| ------------------ | --------: | -------------------------- | ---------------: | --------------------: |
| small              |       646 | 0.180 / 0.166 / 0.348      |          1765424 |               2097152 |
| multi              |      1050 | 0.212 / 0.212 / 0.212      |          1765424 |               2097152 |
| byte-heavy         |  14761136 | 17.302 / 10.249 / 10.088   |         86223864 |             111149056 |
| entry-heavy        |  13700372 | 10.175 / 11.731 / 10.838   |        152566408 |             165675008 |
| wire-max           |  16777216 | 10.914 / 10.531 / 10.679   |         88271056 |             117440512 |
| noncanonical-large |  14761137 | 9.432 / 9.618 / 10.456     |         86223880 |             111149056 |

Peak érték mindhárom ismétlésben azonos volt. Az első öt scenario VERIFIED,
noncanonical-large DENIED/MANIFEST_NONCANONICAL az elvárt sikeres tesztkimenet.
Külön --td04-r1:128M, raw300379 bájt,0.647s,peak used11817472,
peak allocated12582912,exit0,stderr üres,100 errors,truncated=true,manifest=null,
DENIED/MANIFEST_SCHEMA_INVALID; PASS. Nincs budget-emelés vagy rejtett retry.
Ezek helyi szerzői mérési eredmények; nem production kapacitásígéret, és nem
független review vagy projektgazdai acceptance. A teljes canonical maximum manifest
nem futott. A végrehajtás nem módosított történeti evidenciát és nem indított PoC-t.

**TD05–TD06 NOT IMPLEMENTED. WP7-F01/F02 OPEN. TRANSACTION PoC NOT READY.**
