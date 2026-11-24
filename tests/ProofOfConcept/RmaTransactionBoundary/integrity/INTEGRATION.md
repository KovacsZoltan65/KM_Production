# TD06 — Integration contract és negatív consumer proof

Hatókör: a részleges A1.2 és a későbbi teljes A1 verifier közötti szerződés,
dokumentáció és standalone negatív tesztek. Ez nem runtime-integráció vagy
production authorization consumer. Implementálva, független review-ra vár.

Irányadó: [P9B](../../../../.kiro/designs/rma-0004-c3-02-9b-trusted-digest-verification-implementation-plan.md)
§8 TD06, §9 TD-T37–40; további kötöttségek: §4, §6.1–6.5, §7, §10–12, §14.
[C029A](../../../../.kiro/designs/rma-0004-c3-02-9a-manifest-integrity-implementation-plan.md)
§10–12 a historical/current és future VM boundary forrása. A régi tervek
implementációs státuszai történeti állapotok; a tényleges API-k alább szerepelnek.

## Elfogadott komponensek és tényleges API-k

| Komponens | Felelősség és publikus belépés                                                                                                                     | Forrás                                                             |
| --------- | -------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------ |
| A1.1      | Decoded schema/path-validáció: `ManifestValidator::validate(mixed): ValidationResult`                                                              | [ManifestValidator.php](src/ManifestValidator.php)                 |
| TD01      | Selected-anchor/context binding és raw digest: `new TrustedDigestVerifier(AnchorProvider)`, `verify(mixed, mixed, string): DigestComparisonResult` | [TrustedDigestVerifier.php](src/TrustedDigestVerifier.php)         |
| TD02      | Strict JSON parsing: `StrictJsonTokenizer::parse(string): JsonParseResult`                                                                         | [StrictJsonTokenizer.php](src/StrictJsonTokenizer.php)             |
| TD03      | Canonical bytes: `CanonicalJson::serialize(Manifest): string`, `isCanonical(Manifest, string): bool`                                               | [CanonicalJson.php](src/CanonicalJson.php)                         |
| TD04      | Manifest-only orchestration: `new ManifestDigestVerifier(AnchorProvider)`, `verify(mixed, mixed, string): ManifestVerificationResult`              | [ManifestDigestVerifier.php](src/ManifestDigestVerifier.php)       |
| TD05      | Bounded content acquisition: `ManifestSnapshotReader::read(ManifestByteSource, string): ManifestSnapshot`                                          | [ManifestSnapshotReader.php](src/ManifestSnapshotReader.php)       |
| TD06      | Ez a szerződés és C06-T01–T12 test-only consumer-proof                                                                                             | [ManifestVerificationTest.php](tests/ManifestVerificationTest.php) |

A `TrustContext` explicit caller-input: mode, independent selection origin/ref,
expected kilencmezős anchor tuple. Az `AnchorProvider(?TrustAnchor)` fixed,
injektált selectiont ad; nincs candidate/env/CWD/config fallback vagy store-lookup.
Az `AnchorValidator::validate(mixed): TrustAnchor|string` exact decoded stdClass
boundary: raw anchor JSON stringet nem parse-ol hallgatólagosan. Raw deliverynél
a strict anchor parsing külön caller/delivery-felelősség, nem a manifest parser
digest-before-parse követelménye alóli kivétel.

TD03 preconditionje a sikeres A1.1-validációból származó immutable Manifest;
a publikus DTO-konstruktor nem validációs vagy provenance-bizonyíték.

## Result, scope és trust

A [ManifestVerificationResult](src/ManifestVerificationResult.php) minden ágon:

```text
scope = MANIFEST_ONLY
fileInventoryStatus = NOT_EVALUATED
filesystemIdentityStatus = NOT_EVALUATED
executionAuthorization = NOT_EVALUATED
canLaunchPoC = false
```

Sikeres `manifestVerdict=VERIFIED` csak a G01–G09 manifest-scope sikere.
A `verdict` ugyanennek readonly aliasa, nem teljes inputVerdict. Failure-nél
`manifest=null`; nincs partial DTO success. Az `anchorHistoryStatus` mindig
`NOT_EVALUATED`. A result nem tartalmaz eredeti raw payloadot vagy mutable
parsergráfot; a private konstruktor/factory tényleges ellenőrzést futtat,
unserialization tiltott. Ez nem same-process arbitrary-code sandbox.

A manifest schema inventory-mezőinek validálása nem tényleges file enumeration
vagy file-content hashing. Canonical/profile binding nem az actual profile-file
hash-ének mérése. Nincs teljes input-, native identity-, ledger freshness-,
execution authorization- vagy PoC-readiness bizonyíték.

`FIXTURE` → `authenticity=SIMULATED`; `OPERATOR_ATTESTED` → ugyanilyen nevű
explicit helyi minősítés. Egyik sem protected deployment approval.
Az operator-minősítés nem független bizonyítéka az operátori record külső
eredetének. `ACTIVE` és `approvalRef` nem signature, revocation-freshness vagy
globális manifest-ID ledger proof. A selected release/profile binding megmarad,
de a caller/verifier/context fölött rendelkező same-user támadó ellen nincs
garancia. Deployment capability-kérésre nincs local downgrade.

## Acquisition és életciklus

TD05 maximuma minden eredményben `assurance=CONTENT_ONLY`:

```text
maximum accepted raw bytes = 16777216
maximum acquired bytes = 16777217
maximum read request = 65536
```

Siker: `acquired=true`, eredeti binary `rawBytes`, pontos `byteCount`, nincs
verification-verdict. Failure: `acquired=false`, `rawBytes/byteCount=null`;
partial buffer nem adható tovább, üres stringgel sem helyettesíthető.
Az acquisition nem digest/schema/canonical/provenance/native/authorization proof.

A `ManifestByteSource` trusted adapter/test interface; `open`, `expectedLength`,
bounded `read`, `eof`, advisory `stat`, `close` műveletekkel.
`new LocalManifestByteSource(string $approvedRoot, string $sourcePath)` explicit
fully qualified local konfigurációt kér. Nincs source discovery, URL/UNC/device/
ADS/CWD/env fallback. Az adapter egyszer nyit; a reader lezárja a sikeresen
megnyitott forrást, TD04 nem vesz át handle-t. A snapshot birtokolja a visszaadott
immutable bájtértéket; a handle addigra lezárt. Injected source nem local provenance.

Rövid read folytatható; false read, nulla progress EOF nélkül, declared-length
eltérés vagy stat/close failure megállít. **TD05-D01=A**: advisory stat drift
`IO_READ_FAILED/INCONCLUSIVE`, nem native `FILE_CHANGED` bizonyíték.
Az első established acquisition failure megmarad későbbi cleanup failure mellett.
Unsupported assurance `CAPABILITY_UNAVAILABLE/INCONCLUSIVE`, open/read nélkül;
oversize `MANIFEST_SIZE_EXCEEDED/DENIED`. Programhiba továbbterjed.

## Gate-sorrend és a halasztott fájlalapú integráció

```text
G01 — Context and capability
G02 — Anchor
G03 — Acquisition and size
G04 — Digest
G05 — Strict parser
G06 — A1.1 validation
G07 — Canonical representation
G08 — Binding
G09 — Result
```

TD04 már megszerzett stringet fogad. A naiv `TD05.read() → TD04.verify()`
előbb acquisitiont végez, majd TD04 G01/G02 validációját: **nem teljes, helyes
sorrendű fájlalapú verifier**. A jelenlegi public API-k nem adnak külön
újrahasználható G01/G02-preflight capabilityt a G03 acquisition elé.
Az internal `evaluate()` facts nem ilyen capability.

Future file-backed integrációhoz külön megtervezett preflight-boundary szükséges.
TD06 nem implementál ilyet, nem változtat API-t és nem ad workaroundot.
Az első hibás gate kötelezően megállít; a későbbi statusok NOT_EVALUATED maradnak.
G02 megelőzi a readet; G04 mismatch megelőzi a manifest parsingot; schema/path
hiba megelőzi a noncanonical hibát, az pedig a decoded binding hibát.
P9B §7/§10 precedence és existing hibakódok változatlanok.

G06 legfeljebb100 eredeti A1.1 code/path/message diagnosticot visz át, pontos
diagnosticsTruncated értékkel; nincs101. summary error vagy új budget entrynként.
Stabil diagnosticba nem kerül raw payload, secret, abszolút forrásút, timestamp
vagy OS exception-szöveg. Általános programhiba nem válik fabricated resulttá;
INTERNAL_ERROR mapping csak későbbi külső CLI-boundary lehetősége.

Egyszer megszerzett eredeti snapshot szükséges digesthez, parserhez és canonical
comparisonhöz. Nincs hash_file + reopen, rewrite vagy pin-javítás. Ez byte-snapshot,
nem atomi directory/physical-file snapshot vagy verify/use race-védelem.

## Historical/current separation

Az actual historical evidence pontos útja
[`../evidence/results.json`](../evidence/results.json), P9B §2.2/§11 és C029A §10.
Előírt raw SHA-256:
`a2e52bc4f68efea95035caa8ec8ddcac9bf941ee13bf30f516f39312449bc9b5`.
Inert történeti bizonyíték; nem current manifest, current trust vagy execution
eligibility. Nincs implicit migráció, rewrite, reclassification vagy regenerate.

C06-T09/10 kizárólag kicsi, memóriabeli synthetic historical adatot használ;
actual historyt nem másol, módosít vagy hajt végre. Matching fixture digest mellett
a legacy shape G06 `MANIFEST_SCHEMA_INVALID`; módosított synthetic copy fixed
eredeti pin mellett G04 `MANIFEST_UNTRUSTED`. Nincs új legacy-format hibakód.

## Test-only consumer proof és requirement-mapping

A `ManifestVerificationTest` private `consumerRefusals`/`consumeForTest` helperje
csak refusal-policy modell. Genuine TD04-resultot vizsgál; a MANIFEST_ONLY
pozitív kontroll elfogadható adatfogyasztás, launch nélkül. Erősebb kérésnél a
kapott scope/status/authenticity és hiányzó vagy STALE synthetic proof-címkék
magyarázzák a refusal-t. A private magyarázatok nem publikus verdict/error kódok.

`CURRENT` synthetic címke nem független ledger/native/freshness proof, és nem
emeli fel a TD04 scope-ját. Nincs clock vagy freshness service. C06-T08 plain
`SYNTHETIC_CONTENT_ACQUISITION` envelope-ot használ: nem sikeres ManifestSnapshot,
és nem actual reader- vagy fájlalapú integrációs teszt. Nincs unsafe hydration.

C06-T12 ugyanazt a C06-T04 regressziót futtatja két izolált PHP childban.
Elfogadott forrás: exit0. Csak memóriában kiiktatott refusal-return: az intended
`C06-T04 unauthorized execution was accepted` assertion miatt exit1. A parent
ellenőrzi a pontos exitet, JSON failure-t és üres stderr-t; harness/mutation-hiba
nem számít várt failure-nek. Tracked forrás nincs átírva.

| Követelmény                            | Konkrét bizonyíték                                                                                                        |
| -------------------------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| P9B §8 explicit scope, release/profile | C06-T01–T03; a fenti API/result táblázat                                                                                  |
| §8 local/deployment/execution külön    | C06-T04–T08; result/trust fejezet                                                                                         |
| TD-T37 no import/call                  | C06-T11 runner/source token-vizsgálat, actual include-lista, file trap és zero callback; meglévő TD01/TD04 no-I/O tesztek |
| TD-T38 history/current                 | C06-T09/10 és külön actual historical hash preservation                                                                   |
| TD-T39 consumer refusal                | C06-T04–T08; valódi production consumer nincs implementálva                                                               |
| TD-T40 regression/nonzero              | Eredeti A1.1 suite, focused/combined katalógus és C06-T12 child exit1                                                     |

Az include-map és callback proof nem OS/network sandbox, és nem bizonyítja,
hogy egy tényleges launcher vagy VM consumer biztonságossá vált.
A tesztparancsok, C06-katalógus és actual eredmények a [README-ben](README.md).

## Erőforrások, függőségek és kizárások

Nincs új acquisition-buffer, reopen, source discovery, native check vagy budget.
TD06 fixture-jei kicsik és memóriabeliek; a meglévő regresszió own-fixture registry
és exact cleanup szabályai változatlanok. A combined 512MiB tesztkonfiguráció nem
új kapacitásígéret; TD04-D01 18-run/30s protokoll nem TD06 acceptance-kritérium.
TD05 synchronous I/O-ja nem garantál általános bounded blocking time-ot.

A valódi Windows symlink létrehozása a korábbi standard-user mérésben unavailable
volt. Injected REPARSE_FOUND teszt nem actual symlink vagy native identity PASS.
realpath/lstat/fstat/is_link nem általános Windows reparse/hardlink/ADS/file-ID proof.

TD04 accepted result közvetlen függőség; TD05 csak file-backed use-hoz kell.
Full Windows consumerhez A1.3–A1.6/C028 külön integráció és engedély szükséges.
Nincs teljes inventory/native/protected-store/ledger/freshness/CLI/result-writer/
bootstrap/launcher/app integráció, SQL, VM vagy transaction PoC.
TD-D07/TD-D09 halasztott; TD05-D01=A nem nyílik újra.

**WP7-F01/F02 OPEN. TRANSACTION PoC NOT READY.** TD06 nem zárja le a transaction
runtime proof és izolált környezet/evidence fennmaradó hiányait, és nem ad
execution authorizationt.
