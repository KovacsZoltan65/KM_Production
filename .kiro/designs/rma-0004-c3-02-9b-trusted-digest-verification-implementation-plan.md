# RMA C3-02.9-A1.2 — Trusted Digest Verification Implementation Plan

Dátum: 2026-11-18. Hatókör: dokumentáció és statikus forráselemzés.
Státusz: **PLAN COMPLETE — IMPLEMENTATION APPROVAL REQUIRED**.
Owner döntés (2026-11-18): TD-D04 **ACCEPTED WITH EXPLICIT WIRE-DOMAIN RESTRICTION**,
Option A: az A1.1 decoded-domain változatlan, az A1.2 eredeti raw input capje
pontosan 16777216 bájt (§6.3.2). TD-D02/03/05/06 elfogadása megmarad.
Az architectural contract elfogadott; runtime memóriaigazolás és minden
TD01–TD06 implementáció külön későbbi engedélyhez kötött.
Nem készült PHP-kód, actual manifest, anchor, fixture vagy executable bundle.

## 1. Vezetői összefoglaló

Az A1.1 a projektgazda által elfogadott, commitolt pure adatvalidátor.
Az A1.2 tervezett alapja a trusted caller által kiválasztott independent
anchor és a kapott manifest **eredeti bájtjainak** SHA-256 összevetése.
A digest-mismatch hard stop: utána nincs manifest-tokenization, JSON decode,
canonicalization vagy A1.1-hívás. Egy sikeres hash-összevetés még nem schema-,
file-inventory-, Windows-identity- vagy execution proof.

Ajánlott sorrend: pure anchor/digest core (TD01), külön strict parser
(TD02), külön canonical serializer (TD03), ezek és A1.1 összekötése (TD04).
A filesystem-backed single-snapshot reader TD05, az A1.3-hoz kapcsolódó külön
scope; az integrációs szerződés TD06. TD01 önállóan tesztelhető, de TD02–04
nélkül nem nevezhető teljes raw-manifest verificationnek.

A helyi módok: explicit `FIXTURE` / `authenticity=SIMULATED`, illetve
`OPERATOR_ATTESTED` független operátori record-kiválasztással. Protected
deployment store/bootstrap nincs implementálva és nem helyettesíthető egy
repositoryban tárolt, önmagát `ACTIVE`-nak nevező anchorral. Nincs automatikus
átlépés deployment authenticityra, nincs env/CWD fallback vagy anchor-generálás.

A jóváhagyott gate-, canonical-, result-, trust-context és resource-domain
döntések átvezetve. TD-D04 **ACCEPTED WITH EXPLICIT WIRE-DOMAIN RESTRICTION**:
az A1.2 csak strict-parsed, canonical, A1.1-valid, legfeljebb16MiB eredeti raw
reprezentációt fogad, independent anchor- és binding-ellenőrzéssel. Az A1.1
érvényesség önmagában nem elég. A canonical maximum statikusan igazolt
44584955 bájt; §6.3.2 reprodukálható túlméretes konstrukciót ad, nem fixture-t.
A §14 szerint minden implementációs szelet **NOT AUTHORIZED**; a runtime
memóriakapuk a döntés elfogadásától külön megmaradnak.
A1/B2/C2, D1–D4 és A1.1-T01 változatlan. WP7-F01 OPEN — BLOCKER;
WP7-F02 OPEN — PARTIALLY VERIFIED — HIGH. A transaction PoC **NOT READY**.

## 2. Forrás- és baseline-leltár

### 2.1. Közvetlen repository-állapot

| Tétel                                 | Ellenőrzött érték                                                |
| ------------------------------------- | ---------------------------------------------------------------- |
| Repository                            | `C:\wamp64\www\km\KM_Production`                                 |
| Branch                                | `feature/returns-repair-management`                              |
| HEAD                                  | `5448f369d152d7bc288f148f2f898b41880f428e`                       |
| Upstream                              | `origin/feature/returns-repair-management`, ugyanaz a HEAD       |
| Ahead / behind                        | `0 / 0`, `git rev-list --left-right --count HEAD...@{upstream}`  |
| Kezdő tracked/index/untracked eltérés | Nincs; teljes status és cached name-list üres                    |
| Elfogadott A1.1 commit                | `test(rma): add standalone manifest schema validation`, hét fájl |
| Jelen feladat egyetlen kimenete       | Ez az új Markdown terv, uncommitted                              |

Kezdő megőrzési leltár: **945 fájl**, összesített SHA-256:
`86d38f968fcb9e81f1a9fb0a7fde283e24480b051768b70c844fc2f6cc78a3f4`.
Algoritmus: `git ls-files -z --cached --others --exclude-standard` egyedi,
rendezett UTF-8 pathjaihoz regular file-onként raw SHA-256; a `(path,hash)`
listára Python `json.dumps(..., ensure_ascii=False, separators=(',', ':'))`,
majd annak UTF-8 SHA-256 értéke. Ez munkafa-megőrzési ellenőrzés, nem trusted
manifest, ignored/outside-inventory file-ra vagy OS-hozzáférésre nem bizonyíték.
Külön leltárfájl nem keletkezett.

Döntésvéglegesítés baseline (2026-11-18): ugyanaz a branch/HEAD, pontosan
az itt módosított terv az egyetlen untracked fájl; tracked módosítás nincs,
az index üres. A §2.1 tiszta kezdő állapota a terv eredeti elkészítésének
baseline-ja; az egyetlen új fájl megmarad uncommitted. E feladatban második
terv nem jött létre.

### 2.2. Forráskulcsok

A három design dokumentum teljesen olvasott. A sorhivatkozások 1-alapúak,
a fenti HEAD-hez tartoznak; a `§` az adott dokumentum saját fejezete.

| Kulcs | Forrás és pontos relevancia                                                                                                                                                                                                                                                                                                                         |
| ----- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| C029A | [C3-02.9-A](rma-0004-c3-02-9a-manifest-integrity-implementation-plan.md), §4–5 exact data/anchor/byte contract; §6 native határ; §7 V01–V14/result/exitek; §8–12 fájlok, MI01–36, history és slice-ok; §14 nyitott döntések                                                                                                                         |
| C028  | [C3-02.8](rma-0004-c3-02-8-execution-architecture-specification.md), §3–4 A1/B2/C2 és timestamp; §4.3 trust/TOCTOU; §4.4 korábbi sorrend/result; §9 evidence; §10/12–14 integráció és authorization                                                                                                                                                 |
| C027  | [C3-02.7](rma-0004-c3-02-7-poc-execution-safety-remediation-plan.md), §3.1–3.3 történeti hash és A1; §7–8 függőségek; §10–13 külön végrehajtási kapuk                                                                                                                                                                                               |
| J     | [A1.1 JSON schema](../../tests/ProofOfConcept/RmaTransactionBoundary/integrity/schema/input-manifest-v1.schema.json), 1–121: Draft2020-12 adatleírás, nem runtime engine                                                                                                                                                                            |
| M     | [Manifest.php](../../tests/ProofOfConcept/RmaTransactionBoundary/integrity/src/Manifest.php), 7–26: readonly, data-only DTO; nincs raw digest vagy authorization                                                                                                                                                                                    |
| V     | [ManifestValidator.php](../../tests/ProofOfConcept/RmaTransactionBoundary/integrity/src/ManifestValidator.php), 11–26 limits/fields; 28–35 dedikált termination catch; 39–163 pure validáció; 171–194 object/unknown fields; 250–267 timestamp/path; 275–285 100-error stop                                                                         |
| VR    | [ValidationResult.php](../../tests/ProofOfConcept/RmaTransactionBoundary/integrity/src/ValidationResult.php), 9–55: valid/errors/manifest, deterministic path/code/message sort és optional serialized truncation                                                                                                                                   |
| T     | [ManifestValidationTest.php](../../tests/ProofOfConcept/RmaTransactionBoundary/integrity/tests/ManifestValidationTest.php), 14–355 eredeti cases; 357–448 R1 cases; 457–484 fixture/assertion helpers                                                                                                                                               |
| TR    | [run-tests.php](../../tests/ProofOfConcept/RmaTransactionBoundary/integrity/tests/run-tests.php), 7–17 saját require-map + schema-read; 20–30 failure capture/nonzero exit                                                                                                                                                                          |
| RD    | [integrity README](../../tests/ProofOfConcept/RmaTransactionBoundary/integrity/README.md), Field contract / Result és error contract / JSON schema és PHP különbségek: parser/trust/native deferral és R1                                                                                                                                           |
| H     | [Történeti evidence](../../tests/ProofOfConcept/RmaTransactionBoundary/evidence/results.json): inert historical eredmény, nem current input-manifest vagy anchor                                                                                                                                                                                    |
| L/R/P | [launch.ps1](../../tests/ProofOfConcept/RmaTransactionBoundary/launch.ps1), 338–340 history-existence stop; [run.php](../../tests/ProofOfConcept/RmaTransactionBoundary/run.php), 9–13 P-import + stop; [Prototype.php](../../tests/ProofOfConcept/RmaTransactionBoundary/Prototype.php), 13 vendor autoload; kizárólag statikusan olvasott határok |

C029A raw SHA-256: `95750c86de891378c71dc3c7c39493c5803beabf9d72b6e4f8343670270e807b`.
C028: `125746a84ea9f8d1e746ec765a7609bd0f66d71f5d5e01225562a86cd8b8a1ab`.
C027: `30e03b89ac61e1d33d8dfa96a07c308c8f8bfd0ce6b05eaacfdb7fa4afd94c0e`.
H raw SHA-256: `a2e52bc4f68efea95035caa8ec8ddcac9bf941ee13bf30f516f39312449bc9b5`.
A régi tervek saját korábbi baseline-ja és „nem implementált” állapota történeti;
nem írjuk át őket a mai A1.1 acceptance alapján.

### 2.3. A1.1 tényleges képessége és a tervhez képesti határ

V pontos `stdClass` objektumokat és PHP listákat fogad, nincs decode vagy I/O.
Minden root/entry/metadata mező kötelező, unknown field tiltott; nincs coercion.
Egyszerre ellenőrzi a mezőket, timestampet, listákat, pathokat, case collisiont,
diszjunkt inventoryt és combined limiteket. M csak hibamentes végén készül.
VR `toArray()` alapja `valid/errors`; csak küszöbös leállásnál kerül bele
`diagnosticsTruncated: true`, miközben a property egyébként `false`.

M-ben nincs `rawDigest`; ezt külön wrapper/result tartja majd, az elfogadott
DTO-t nem bővítjük hallgatólagosan. A1.1 nem ismer nyers duplicate key-t,
integer-token eredetet, BOM-ot vagy canonical bytes-t. A README ezt kifejezetten
halasztja. C029A §12 eredeti A1.1 sorának parser/canonicalization része ezért
**nem megvalósult képesség**; TD02–03 külön előfeltétel, nem A1.1 újranyitása.
Az ismert 203-as passing tesztbaseline a korábbi elfogadásból származik;
ebben a documentation-only feladatban **nem futtattunk PHP-tesztet**.

## 3. Scope és non-goals

Tervezett core: explicit local trust context, anchor-validáció, raw-byte digest,
constant-time compare és ezek részleges eredménye; külön raw-parser,
canonicalizer és A1.1-integráció; később külön bounded file reader.
A tíz komponens felelőssége és a dependency DAG a §5/§8 szerint különválik.
A1.2 wire acceptance az A1.1 decoded contract, strict JSON, §6.4 canonical bytes,
raw <=16777216 és independent trust/binding feltételek metszete. A1.1 schema,
validator és limitek változatlanok; nem cél minden A1.1-valid graph wire-elfogadása.
Túlméretre nincs truncation, normalizálás, split vagy automatikus rewrite.

Jelen engedély kizárólag ezt a meglévő untracked dokumentumot módosítja. Nem módosul a hét
A1.1 fájl, a schema, README, AGENTS, ADR-0006, alkalmazás vagy migration.
Új PHP/dependency/fixture/anchor/manifest/output nem keletkezik. Git write tilos.

Sem a későbbi A1.2 core, sem e terv nem enged transaction PoC-t, L/R/P/W importot
vagy indítást, SQL/MySQL-kapcsolatot, VM provisioninget, account/ACL/UAC-változást,
launcher-adaptációt, history-rewrite-ot, automatic approvalt vagy executiont.
A valódi file inventory/hash, profile-file content hash, native identity,
held-handle TOCTOU proof, output writer/CLI és protected deployment store
C029A A1.3–A1.6 / C028 külön későbbi kapui maradnak.

## 4. Trust-boundary elemzés

| Boundary / mód             | Megbízható bemenet és ellenőrizhető állítás                                                                                     | Nem igazolható állítás / stop                                                                                               |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| Untrusted manifest bytes   | Csak a megszerzett immutable byte-snapshot tartalma; nincs belőle anchor-selector                                               | Manifest mezőből, URL/pathból vagy saját hashből nincs trust; bundle-controlled selector tiltott                            |
| Trusted selection → anchor | Független caller előre kiválasztott exact record-bindingje; candidate ehhez hasonlítandó                                        | Candidate saját `anchorId/status/approvalRef` értékei nem hitelesítik a kiválasztást                                        |
| `FIXTURE`                  | Trusted test harness injektál synthetic recordot és inputot; `trustMode=FIXTURE`, `authenticity=SIMULATED`                      | Nem production provenance; az input+anchor együtt módosítható a teszt ownerétől                                             |
| `OPERATOR_ATTESTED`        | Operátor repository/bundle-tól független approved recordból választja az expected tuple-t és digestet; explicit attestation ref | Nem same-user attacker elleni védelem; a mutable helyi script/memória/CLI/record kompromittálható                           |
| Future deployment          | Protected approval-store és pinned trusted bootstrap/verifier release, független delivery/provenance proof                      | Most unsupported; kérésére `CAPABILITY_UNAVAILABLE`, nincs visszaesés local módba vagy `EXTERNAL_ANCHOR` önbevallás         |
| A1.1 data validity         | A megadott decoded graph megfelel a pure schema/path szerződésnek                                                               | Nem raw integrity, authenticity, inventory measurement vagy indítási engedély                                               |
| File reader / OS           | Local reader csak a visszaadott bytes-t vizsgálhatja `CONTENT_ONLY` határral                                                    | `fstat/realpath` nem általános Windows reparse/hardlink/ADS/identity proof; strong kérés native adapter nélkül inconclusive |

Nincs mód-default vagy implicit promotion. Trusted contextet a subsystem nem
származtat a CWD-ből, environmentből vagy bundle-ból; nincs `--trusted-digest`
mint önálló protected-bootstrap bizonyíték. A mode, release és profile a caller
által előre kötött; candidate record nem változtathat rajtuk.

Anchor substitution ellen a **kiválasztás** independent: azonosító helyett a
selected tuple tartalma is kötött (§6.1). Ha támadó csak candidate-t/manifestet
cserél, binding vagy raw digest mismatch lesz. Ha a trusted selectiont és a
verifiert is írhatja, a helyi program nem képes az independent rootot pótolni.

## 5. Komponensarchitektúra

### 5.1. Felelősség, input/output és trust

Az alábbi nevek tervezettek; az egyetlen létező komponens itt C09.

| ID / komponens                                              | Felelősség                                                             | Input contract → output contract                                                                                  | Trust assumption                                                                                    |
| ----------------------------------------------------------- | ---------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| C01 `ManifestSnapshotReader`                                | Bounded acquisition, egyetlen nyitott read stream, egyszeri snapshot   | Explicit approved local source + cap → immutable raw bytes/byteCount vagy acquisition failure                     | A caller/source policy trusted; read API nem OS-identity oracle                                     |
| C02 `AnchorProvider` + `TrustContext`                       | Independent exact anchor kiválasztás, mode/release/profile binding     | Explicit selection, nincs fallback → selected candidate record + origin/attestation ref, vagy missing/unavailable | Selector és provenance a calleré; deploymenthez külön protected store kell                          |
| C03 `AnchorValidator`                                       | C029A §5.2 anchor field/type/version/status ellenőrzés                 | `stdClass` vagy C06-on strict-parsed anchor → validated `TrustAnchor` vagy structured failure                     | Decoded fixture raw duplicate-proofot nem állít; raw adapter nem használhat guard nélküli decode-ot |
| C04 `TrustedDigestVerifier` hash része                      | Raw-byte SHA-256, normalizálás nélkül                                  | Bounded PHP binary string → 64 lowercase hex actual digest                                                        | Approved runtime és hash capability; nem file-existence vagy origin proof                           |
| C05 `TrustedDigestVerifier` compare része                   | Syntax-checked expected/actual constant-time összevetés                | Két exact 64 lowercase hex string → `MATCH` vagy `MISMATCH` részállapot                                           | Expected digest csak C02/C03 trusted bindingből jön                                                 |
| C06 `StrictJsonTokenizer`                                   | Bounded grammar/UTF-8/BOM/depth/numeric-token decode                   | Ugyanaz a digest-matched raw snapshot → `stdClass`/list/scalar graph vagy első parser failure                     | Nem hív appot, nincs coercion/repair; parser saját helyessége golden/negative review-t igényel      |
| C07 C06 object-key guard                                    | Decoded kulcs-egyenlőség vizsgálata overwrite előtt                    | Egy object decoded key-jei + bounded per-object seen-set → unique keys vagy `JSON_DUPLICATE_KEY`                  | Case-sensitive byte-equality; escaped ekvivalencia ugyanaz a kulcs                                  |
| C08 `CanonicalJson`                                         | Exact v1 serializer és byte-egyezés                                    | A1.1-valid graph + eredeti raw bytes → exact equality vagy `MANIFEST_NONCANONICAL`                                | Szűk manifest-domain; nem általános RFC8785, nem input-javító szolgáltatás                          |
| C09 meglévő `ManifestValidator`                             | Elfogadott pure field/path/timestamp/inventory szabályok               | C06 decoded data → jelenlegi `ValidationResult`, max100 errors, nullable Manifest                                 | A1.1 változatlan, data-only eredmény; raw origin nem a felelőssége                                  |
| C10 `ManifestDigestVerifier` + `ManifestVerificationResult` | G01–G09 orchestration, identity/profile/release binding, scoped result | Explicit context + candidate + snapshot → §6.5 result                                                             | Nem teljes A1 verifier; nem állítja a V08–V12 file/native proofot vagy authorizationt               |

### 5.2. Failure, side effect, dependency, teszt és integráció

| ID  | Failure behavior                                                                                            | Side effect                                                                        | Dependencies                                                     | Unit-test és integration boundary                                                                                        |
| --- | ----------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- | ---------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| C01 | Size/read/no-progress/short snapshot hiba: nincs parse; partial bytes nem kerülnek tovább                   | Csak későbbi explicit own-source open/read/close; nincs write/network/process      | Injected read source; local regular-file adapter külön TD05/A1.3 | Chunk/EOF/failure spy; D fixture csak későbbi engedéllyel; C04/C06 ugyanazt a snapshotot kapja                           |
| C02 | Missing anchor hard deny; unavailable protected capability inconclusive; nincs keresés/retry másik recordra | Pure injected provider: nincs I/O; külső store adapter saját későbbi read boundary | Explicit `TrustContext`; immutable selected record               | Selector/candidate substitution, mode downgrade, call-count; nem keverhető manifest acquisitionnel                       |
| C03 | Első anchor hiba deterministic; invalid DTO nem adható tovább                                               | Nincs I/O/input/global mutation                                                    | C06 csak raw anchor deliverynél, tiszta decoded unit API külön   | Mind a kilenc required field, null/type/unknown/version/status/hash; C04 előtt fut                                       |
| C04 | Oversize vagy missing capability: nem készül matched result                                                 | Csak CPU/memória; nincs `hash_file()` vagy input újraolvasás                       | SHA-256 runtime capability, 64-bit és raw-size gate              | Known-answer, NUL/CRLF/LF/egy byte drift; parse call-count0 mismatchnél                                                  |
| C05 | Invalid digest syntax külön input/anchor error; mismatch után stop                                          | Csak memória; nincs napló/clock/env hozzáférés                                     | `hash_equals(expected, actual)` mint tervezett primitív          | Known equal/unequal pairs; ne timing-méréssel „bizonyítsuk” a constant-time tulajdonságot; implementation review is kell |
| C06 | Első balról talált lexical/token hiba; nincs részleges decoded-success                                      | Csak bounded memória; nincs locale/date/global state                               | Byte cursor, explicit bounds, C07                                | Grammar/encoding/overflow/depth vectors; C04/05 manifest-match után hívható                                              |
| C07 | Duplicate decoded key azonnali parse stop, nincs last-key-wins                                              | Per-object bounded stack/set, unwindkor elengedve                                  | C06 string decoder                                               | `path` / `\u0070ath`, nested duplicates, numeric-looking keys, distinct-object control                                   |
| C08 | Nem egyező bytes elutasítás; nem írja vissza a canonical változatot                                         | Bounded output-memory; nincs file writer, nincs graph mutation                     | C09 siker + explicit ASCII serializer                            | Golden bytes, final LF/CRLF/escape/key order negatives; inventoryt nem rendezi át                                        |
| C09 | A 100. hiba után saját dedikált signal → invalid/null/truncated; más exception továbbterjed                 | Nincs I/O, input/global mutation                                                   | M/VR, saját helper-ek                                            | Eredeti203 regresszió később változatlanul; C10 egyszer hívja, nem rekreálja a mezőszabályokat                           |
| C10 | Bármely hard gate stop; nincs partial DTO success; programming exception továbbterjed                       | Pure API: nincs I/O, writer, time vagy process; későbbi CLI külön boundary         | C02–09, opcionálisan C01 eredménye                               | Pipeline spies, lifecycle reuse, max100 diagnostics, deterministic result; nem drop-in teljes V01–V14 result             |

Közös dependency-tilalom: nincs vendor/Laravel/Pest/Artisan/PDO/config/.env
bootstrap, nincs L/R/P/W require vagy launcher callback. Az új runner majd
csak saját statikus require-mapet bővíthet. Nem adunk plugin/dependency
telepítési feladatot; a szűk parser indoka C029A §4.2 duplicate-key guardja.

## 6. Exact data contractok — döntésvéglegesítés és implementációs határ

### 6.1. Anchor és trusted selection

C029A §5.2 kilenc mezője **mind kötelező**, optional mező nincs. Pontos object,
nincs unknown field/default/coercion. A grammar ugyanaz az ASCII ID/hash
grammar, mint C029A §4; a status nem szabadon bővíthető enum.

| Mező                | Exact követelmény                         | Binding / failure                                                                    |
| ------------------- | ----------------------------------------- | ------------------------------------------------------------------------------------ |
| `anchorVersion`     | String `1.0`                              | Más string `ANCHOR_VERSION_UNSUPPORTED`; rossz type `ANCHOR_INVALID`                 |
| `anchorId`          | `[A-Za-z0-9._-]{1,80}`                    | Independent selected anchorId-vel exact egyezés                                      |
| `manifestId`        | Ugyanaz az ID grammar                     | Selected manifestId és később M.manifestId ugyanaz                                   |
| `manifestSha256`    | `[0-9a-f]{64}`, nincs trim/casefold       | Independent approved expected digest; raw manifesttel constant-time compare          |
| `profileId`         | ID grammar                                | Selected profile és M.verificationMetadata.profileId egyezése                        |
| `profileSha256`     | 64 lowercase hex                          | Selected profile digest és M metadata egyezése; tényleges profile-file mérése később |
| `approvalRef`       | ID grammar, nem URL/secret/path           | Selected approval record ref; önmagában nem aláírás                                  |
| `verifierReleaseId` | ID grammar                                | Caller által independent pinned release ID, nem a candidate által választott release |
| `status`            | Elfogadott aktív érték kizárólag `ACTIVE` | `REVOKED` ismert explicit deny; minden más status invalid, nincs inactive fallback   |

TD-D06 szerint elfogadott explicit `TrustContext`: explicit `trustMode`, independent selection origin/ref,
expected anchorId/manifestId/manifestSha256/profileId/profileSha256/approvalRef,
pinned verifierReleaseId és kiválasztott `anchorVersion/status` record-binding.
Ez nem manifestbe/anchorba felvett új mezőcsoport, hanem caller input. A candidate
sosem töltheti ki a hiányzó contextet. Local OPERATOR_ATTESTED esetén az operátor
a teljes kiválasztott tuple-t az approved recordból rögzíti, nem az untrusted
candidate-ből másolja. A record tartalma és a kiválasztás eredete külön állítás.

Pure AnchorValidator unit input lehet már decoded record. Raw anchor delivery
esetén ugyanúgy strict duplicate/encoding/unknown-field guard kell; `json_decode`
utáni stdClass nem bizonyítja a raw key-uniqueness-t. A strict anchor parser
a manifest digest comparison **előtt** futhat; a tilalom a manifest parsingjára
vonatkozik. Typed trusted-store API-nál raw JSON duplicate-key állítás N/A,
az API/delivery eredetének bizonyítása külön marad. Anchor canonical byte-formát
C029A nem ír elő: nem tesszük új kötelező wire-contracttá.

Independent store feladata: approved record eredete, aktuális revocation state,
record/verifier védelme, release compatibility policy és történeti ID-ledger.
A pure subsystem a kapott snapshotban látott REVOKED állapotot elutasítja,
de nem tudja bizonyítani, hogy nincs frissebb revocation. Az ID-reuse lokális
ellenőrzése: ugyanazon selected manifestId más candidate digesttel deny.
Külön időpontokban jóváhagyott recordok globális manifestId→digest egyediségét
csak independent immutable ledger zárja le; ennek hiánya nem „reuse verified”.
Anchor update/rollback nem auto-create, nem REVOKED reactivation; új owner review.

### 6.2. Raw bytes, acquisition és comparison

C029A-ból örökölt A1.2 wire cap: **16 MiB = 16777216 bájt**; ez nem
az A1.1 decoded API saját bájtkorlátja (TD-D04 elfogadott domain-különválasztás, §6.3.2). A pure API PHP binary stringet kap;
`strlen` bájtszám, nem Unicode-karakterszám. Már oversized stringnél gate-deny,
nincs hash/parse. Nem ígérjük, hogy a caller túlméretes stringjének korábbi
allokációját a pure API megakadályozza. Empty bytes matching digesttel is
malformed JSON marad. Nincs BOM-/newline-/whitespace-/escape-normalizálás
a digest előtt, és a záró LF is a SHA-256 része.

C01 külön file-backed javaslata: egyetlen explicit helyi regular source
megnyitása, például legfeljebb64 KiB-os darabok, összesen legfeljebb cap+1
beolvasott bájt a túlméret észlelésére. Nincs korlátlan chunk-array/implode,
nincs stream URL/network/pipe/input callback a manifest mezőiből. Rövid
`read` önmagában nem EOF/hiba: tovább olvasunk a cap/EOF szabály szerint.
False read, EOF nélküli nulla progress vagy az injected source előre közölt
expectedLength értékéhez képest idő előtti EOF `IO_READ_FAILED`; advisory stat drift külön jelzett.
Az expectedLength transport-adat, nem új anchor-mező vagy native identity proof.
Ismeretlen teljes hossznál EOF elfogadható acquisition-vég, a trusted digest
is felismeri a hiányos byte-snapshotot. Sem partial buffer, sem előzetes stat
nem változhat sikeres ellenőrzéssé.

A read-only handle minden ágon lezárandó; read failure-nél nincs parserhívás.
A local synchronous file API-ról nem állítunk általános bounded blocking-time
vagy natív identity garanciát. Erősebb timeout/held-handle/root policy TD05/
A1.3 capability review; unsupportedra inconclusive, nem csendes fallback.

A megszerzett immutable snapshot egyszer ad raw bytes-t C04/C06/C08 számára:
hash ugyanebből, parse ugyanebből, canonical comparison ugyanebből. Nincs
`hash_file()` + későbbi új `file_get_contents()` minta. Ez **egy byte-snapshot**,
nem atomi directory/physical-file snapshot vagy verify/use garancia.

Expected digest syntax-ellenőrzése anchor/context-gate-ben történik. Actual
digest raw `SHA-256`, 64 lowercase hex; a tervezett összevetés
`hash_equals(expected, actual)`, azonos ismert hosszal. Normál `===`, laza `==`,
hex→szám conversion vagy whitespace-tűrő összevetés nem helyettesíti.
Constant-time compare nem teszi az egész pipeline-t timing-independentdé.

### 6.3. Strict tokenizer és resource policy

Bounded standalone tokenizer szükséges; guard nélküli `json_decode()` nem elég.
Egy byte-cursor és explicit object/array stack ajánlott, nincs recursive
PCRE-vel JSON-parsing vagy minden prefixre teljes string-másolat. Depth maximum
**16**, a root object depth1; object/array nyitása a következő szintre lép,
string/number nem új szint. A scan előbb a grammar/key guardot végzi, csak
azután rögzíti a tagot a decoded objectbe.

| Terület        | Javasolt exact viselkedés                                                                                                                                                                                                                                             |
| -------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Encoding       | Valódi UTF-8 scan, overlong/invalid continuation/truncated sequence/unpaired surrogate reject; BOM reject. Érvényes Unicode string is csak decoded ASCII-val fogadható a manifest-domainben; nincs normalize/repair/ignore-invalid flag                               |
| Strings        | JSON quote/backslash/escape szabályok; raw control0–31 tiltott; `\uXXXX` decode, surrogate-pair helyesség ellenőrizve. Érvényes non-ASCII pair is domain-deny, nem invalid UTF-8-ként félrecímkézve                                                                   |
| Duplicate key  | Decoded exact byte-egyezés **minden objectben**, overwrite előtt; `path` és `\u0070ath` duplicate. `path` és `Path` külön kulcs, utóbbi majd unknown field. Seen-key tárolás nem okozhat PHP numeric-key coercion collisiont, például prefixed string key használható |
| Grammar        | Egy teljes JSON value + megengedett JSON whitespace; comments, trailing comma, garbage suffix, invalid escape/literal/truncation reject. Az exact single-LF contractot C08 zárja le                                                                                   |
| Numbers        | Manifest-domain: kizárólag `0` vagy `[1-9][0-9]*` integer token. Minus, `-0`, fraction, exponent és plus reject; leading zero JSON-invalid. Hossz/lexikai compare a 64-bit integer maximumával (`9223372036854775807`) **cast előtt**; float fallback nincs           |
| Types          | Object→pontos stdClass, array→PHP list; nincs associative-object/array összemosás. `true/false/null` grammar felismerhető, de a manifest nulltilalmát és mezőtípusait A1.1 dönti el                                                                                   |
| Unknown fields | A tokenizer nem másolja le az A1.1 field/category/size/path szabályait; decoded adatban V utasítja el. A parser általános resource stop nem unknown-field acceptance                                                                                                  |
| Ordering       | Object-key sorrend parserben még nem canonical acceptance; C08 exact equality szükséges. Inventory sorrend/case/duplicates A1.1, nincs automatikus rendezés                                                                                                           |
| Errors         | Első parser failure, stabil code és byte-offset; nincs raw fragment/unknown secret vagy abszolút path a message-ben, nincs teljes hibalista építés                                                                                                                    |

Decoded object-key esetén a javasolt domain-guard printable ASCII-t kér;
NUL/control/DEL kulcs `JSON_KEY_INVALID` még bármilyen PHP property-write előtt.
Ez nem ismétli meg az A1.1 exact mezőlistáját, és érvényes manifestet nem szűkít;
elkerüli, hogy malformed kulcs PHP objektumképzési hibává váljon. A duplicate
guardban az escape-ek feloldása után hasonlítunk, nem a nyers key-token alapján.

#### 6.3.1. Maximum-valid decoded manifest — formális szerkezeti modell

Forrás: V ROOT_FIELDS/METADATA_FIELDS/CATEGORIES és inventory/size/path guardok,
J root/file/history/metadata exact shape; C029A §4.1. Jelölés:
`F = count(files)`, `H = count(historicalArtifacts)`, `N = F + H`.
A resource-modell általános burkoló tartománya `0 <= N <= 100000`.
A ténylegesen A1.1-valid graphban `F >= 1`, `H >= 1`, `2 <= N <= 100000`;
a burkoló nem engedélyez üres inventoryt. Mindkét lista entry-je
exact négymemberes object; a metadata exact négymemberes object.
A „scalar” itt value, nem object key; a string key-k külön számláltak.

| Erőforrás                               | Levezetés                                 | Maximum                             | Minősítés                                                 |
| --------------------------------------- | ----------------------------------------- | ----------------------------------- | --------------------------------------------------------- |
| Root object                             | Egy exact root                            | 1                                   | A1.1 contract                                             |
| Inventory array                         | files és historicalArtifacts              | 2                                   | A1.1 contract                                             |
| Inventory entry object                  | F + H = N                                 | 100000 együtt, nem listánként100000 | Contract + derived count                                  |
| Metadata object                         | Egy verificationMetadata                  | 1                                   | A1.1 contract                                             |
| Combined array element                  | F + H = N; nincs más valid array          | 100000                              | A1.1 contract                                             |
| Összes object                           | Root1 + metadata1 + N                     | 100002                              | Derived                                                   |
| Összes container                        | Root1 + két array + metadata1 + N = N + 4 | **100004**                          | Derived, helyes a korábbi javaslat                        |
| Root scalar value                       | 5 string + exampleOnly boolean            | 6                                   | Exact shape                                               |
| Metadata scalar value                   | 4 string                                  | 4                                   | Exact shape                                               |
| Entry scalar value                      | N × (3 string +1 integer)                 | 400000                              | Exact shape                                               |
| Összes scalar value                     | 6 + 4 + 4N                                | **400010**                          | Derived, helyes; keys nélkül                              |
| Összes string value                     | Root5 + metadata4 +3N                     | 300009                              | Derived                                                   |
| Összes integer value / boolean          | N /1                                      | 100000 /1                           | Derived                                                   |
| Root object member                      | Exact9                                    | 9                                   | A1.1 contract                                             |
| Metadata / entry object member          | Exact4 / exact4                           | 4 /4 objektumonként                 | A1.1 contract                                             |
| Object member összesen / key occurrence | 9 +4 +4N                                  | **400013**                          | Derived; nem a per-object9 limit                          |
| Value/container node összesen           | (N+4) +(4N+10)                            | 500014                              | Derived, keys nélkül                                      |
| Node, ha a key string is külön node     | 500014 +400013                            | 900027                              | Derived; tokenizer számlálási definícióját rögzíteni kell |
| Valid container depth                   | Root1, array2, entry3; metadata2          | 3                                   | Derived; a16-os defensive depth cap ennél tágabb          |

A kilencmemberes korlát **objektumonként** értendő, nem globális member budget.
Globális9 hibás lenne: már a minimum két entry +root+metadata21 member.
Ha a parser globális member guardot is használ, annak legalább400013-at kell
engednie; „node” néven nem szabad scalar/key/container számlálókat összekeverni.

| Decoded string/key osztály                          | Maximum ASCII bájt | Indok                                                     |
| --------------------------------------------------- | ------------------ | --------------------------------------------------------- |
| path value                                          | **240**            | V validPath                                               |
| manifestId/pocVersion/profileId/provenanceRef value | 80                 | V ASCII identifier regex                                  |
| sha256/profileSha256 value                          | 64                 | V lowercase hex regex                                     |
| baselineCommit value                                | 40                 | V lowercase hex regex                                     |
| createdAt value                                     | 20                 | A1.1-T01 whole-second UTC                                 |
| formatId/schemaVersion value                        | 14 /3              | rma-poc-inputs /1.0 exact                                 |
| category value                                      | 13                 | configuration/documentation a leghosszabb elfogadott enum |
| Key                                                 | **20**             | verificationMetadata; minden más exact key rövidebb       |

A közös240-bájtos decoded string/key cap **biztonságos, de key-re nem tight**.
Pontosított defensive javaslat: value max240, key max20; a közös240 is maradhat,
ha a field guard ezt követően A1.1-ben fut. A key20 szigorítás csak invalid
kulcsokat állít meg korábban, nem A1.1-valid mezőt. A per-object9, N100000,
container100004 és scalar400010 nem zár ki A1.1-valid graphot, ha a fenti
számlálási definíciókat alkalmazzák. A további key20/printable-key/anchor16KiB
védelmek implementációs javaslatok, nem az A1.1 módosításai; anchor határra
külön szerződés vonatkozik.

A parser defensive capjei egyetlen egyébként valid **A1.2 wire-domain** inputot
sem zárhatnak ki. A fenti derived A1.1 felső korlátok ehhez biztonságosak,
nem mindegyik érhető el egyidejűleg a16MiB wire-domainben. A számlálók és
decoded string/key hossz limitek ellenőrzése a limitet túllépő allokáció,
stack-push, string-append, seen-set/property/array insertion előtt kötelező;
a túllépő teljes token/graph előzetes felépítése nem bounded guard.
A contract-count, derived count, raw cap, defensive depth/key guard és a
runtime peak-memory külön állítás, egyik nem helyettesíti a másikat.

#### 6.3.2. Raw-size bizonyítás és elfogadott wire-domain korlátozás

**Három külön tartományt kell megkülönböztetni.**

1. **A1.1 decoded domain:** V mixed/stdClass/list adatot kap; nincs raw bytes,
   whitespace, escape-forma vagy byte-size inputja. Önmagában nincs véges raw
   JSON maximuma: ugyanahhoz a valid decoded graphhoz tetszőlegesen sok JSON
   whitespace vagy eltérő escaping tartozhat. Ez nem A1.2 wire acceptance.
2. **Az A1.1-valid graphok elfogadott §6.4 canonical reprezentációja:** itt van
   véges, a graphból levezethető maximum; alább **44584955 bájt**, final LF-fel.
3. **C029A §4.1 szerinti A1.2 wire domain:** ennek örökölt hard capje
   **16777216 bájt (16 MiB)**, canonical formával együtt. Ez az előző
   tartománynak valódi szűkítése, nem az A1.1-ből levezetett mérethatár.

A canonical maximum újraellenőrzött levezetése: J exact required/property
listái és V identifier/path/category/inventory/size/timestamp guardjai alapján,
kizárólag statikus számtannal; az előző jelentés száma nem önálló bizonyíték.
A JSON punctuation, key, quote és scalar hosszak külön újraszámolva:

- A field-grammar egyik valid stringben sem igényel quote/backslash/control
  escape-et; minden karakter egy ASCII bájt. Root két ID-ja80, timestamp20,
  formatId14, schemaVersion3; exampleOnly kötelező false. A metadata értékei
  40/80/64/80 bájt hosszúak lehetnek. Root overhead a listák és metadata
  tartalma nélkül354, metadata338 bájt.
- Maximum stringhosszú file-entry `367 + d_i`, history-entry `439 + d_i`
  bájt, ahol `d_i` a sizeBytes decimal digitjeinek száma. A két array brackets,
  a listák N-2 vesszője és a final LF után a teljes méret:
  **L = 695 + 368F + 440H + sum(d_i)**.
- A maximum N100000-nél és F1/H99999-nél van: egy új, zero-size history entry
  441 bájttal növel, egy file historyra cserélése72 bájttal növel a maximum
  fieldhosszak mellett. Százezer különböző240-bájtos, rendezhető ASCII path
  létezik; a két inventory diszjunktan tartható, reserved/dot szegmens nélkül.
- A declared size budget B=8589934592; egyenként legfeljebb2147483648. A decimal
  digit maximumához mind a100000 entry legalább10000-es (5 digit), majd
  `floor((B -100000×10000)/90000) = 84332` entry100000-es (6 digit) lehet.
  Ekkor a total8589880000 <= B, a maradék54592, és mindegyik size a per-file
  cap alatt van. Így **sum(d_i)=584332**.
- Ez felső korlát is: minden nemnegatív integernél
  `sizeBytes >= 10000 +90000×(d_i -5)` (d1–4-nél a jobb oldal negatív,
  d5–6-nál tight minimum, d7–10-nél kisebb a valódi minimum értéknél).
  Összegzés és B alkalmazása legfeljebb584332 digitet enged, a fenti eloszlás
  ezt eléri. A full canonical maximum ezért
  **695 +368×1 +440×99999 +584332 = 44584955 bájt**.

**Reprodukálható matematikai konstrukció, nem előállított manifest/fixture:**

- Root exact kilenc mező: `formatId=rma-poc-inputs`, `schemaVersion=1.0`,
  `manifestId`80 darab `m`, `pocVersion`80 darab `v`,
  `createdAt=2026-11-18T00:00:00Z`, `exampleOnly=false`, a két alábbi lista
  és a négymemberes verificationMetadata. A timestamp valódi Gregorian nap,
  uppercase T/Z, whole-second UTC, nincs offset/fraction/normalizálás.
- Metadata: `baselineCommit`40 darab `b`, `profileId`80 darab `q`,
  `profileSha256`64 darab `c`, `provenanceRef`80 darab `r`.
- F=1: a file path `p/` +232 darab `a` +`000000`; category `documentation`,
  sha25664 darab `a`, sizeBytes integer0.
- H=99999: history path `h/` +232 darab `a` +az i hatjegyű, nullával kitöltött
  decimal alakja, i=0..99998, ebben a növekvő sorrendben. Minden entry sha256
  64 darab `a`, provenanceRef80 darab `r`, sizeBytes integer0.
- Minden path pontosan240 ASCII bájt (2+232+6), canonical relative path;
  egyik szegmens sem reserved device, dot vagy trailing-dot. A fix szélességű
  index miatt a history byte-order növekvő; a file-lista egyelemű. Az összes
  path különböző, lowercase, a p/h prefix miatt inventoryk között is diszjunkt,
  case-fold collision nincs. N=100000; total declared size0, minden size a
  2GiB egyedi és8GiB combined cap alatt. A hex és ID értékek lexical contractot
  teljesítenek; A1.1 nem követel tényleges file-t vagy actual hash-mérést.
- Minden object key-je recursive ASCII sorrendben, a listák fenti sorrendje
  változatlan; §6.4 szerint nincs escapinget igénylő érték, slash literal,
  nincs space/BOM/CRLF, integer0 és false exact, végén egy LF. Ez
  **695 +368 +440×99999 +100000 = 44100623 bájt**, ami >16777216.

Ugyanez a konstrukció a canonical maximumot is eléri: a file majd a history
sorrendben az első84332 entry sizeBytes=100000, a többi15668 entryé10000.
A total8589880000 és a sum(d_i)=584332; minden további constraint változatlan.
Így a **44584955 bájt ellenőrzött, elérhető matematikai maximum**, nem becslés
és nem futási/memóriaeredmény. Nem generáltunk ilyen adatot vagy fixture-t.

**TD-D04 — ACCEPTED WITH EXPLICIT WIRE-DOMAIN RESTRICTION.** Forrás: a
projektgazda TD-D04 Architecture Decision Finalization feladatának §3–6,
Option A explicit jóváhagyása. A1.1 decoded-domain változatlan marad;
A1.2 wire-domain az alábbi feltételek metszete:

1. V változatlan A1.1 decoded contractja és összes limitje.
2. Strict JSON parsing (§6.3).
3. Approved exact canonical bytes (§6.4).
4. Az **eredeti raw bytes <=16777216**, nem az újraszerializált méret.
5. Minden applicable independent anchor/trust és binding check (§6.1/§7).

A1.1-validity önmagában nem garantál A1.2 wire acceptance-t. A canonical
maximum és a wire cap közötti eltérés elfogadott domain-korlátozás; nincs
megmaradt owner-választási kérdés. G03 túlméretre **MANIFEST_SIZE_EXCEEDED**,
**SHA-256 és manifest JSON parsing előtt** áll meg. Nincs silent truncation,
normalizálás, splitting, automatikus rewrite vagy cap-emelés. A döntés nem
módosít korábbi specifikációt, A1.1-et, historical manifestet vagy evidenciát.

#### 6.3.3. Runtime memória — továbbra is külön acceptance gate

A matematikai graph-/byte-bound nem PHP peak-memory mérés. String/object/hash
és seen-set overhead, copy-on-write, snapshot/decoded/canonical/result egyidejű
élettartam, allocator és process overhead külön mérendő. A maximum-valid input
memóriateszt a **kiválasztott wire-domainre** kötelező, a névleges maximumokat
és az egyidejűleg elérhető maximumokat megkülönböztetve; szükséges PHP limitet
nem találunk ki. A full A1.1 decoded-domain mérés külön kategória, ha engedélyezik.
Az R1 malformed100000-entry128MiB regresszió megmarad; nem bizonyítja a
maximum valid graph128MiB-os feldolgozhatóságát. Fatal/OOM sosem VERIFIED,
structured result előállítása fatal után nem garantálható, fatal/OOM nem
fogadható el sikeres verificationként vagy controlled-failure bizonyítékként.
Maximum-valid A1.2 wire-domain és invalid-input memória külön mérendő,
deterministic controlled failure szükséges. TD-D04 architectural döntése
lezárt; TD02 és a runtime tesztek csak későbbi explicit engedéllyel indulhatnak.

### 6.4. Exact v1 canonical byte contract

C029A §4.2 alapján TD-D03 **ACCEPTED WITH CLARIFICATION**: az alábbi exact
v1 canonical contract elfogadott, kizárólag már A1.1-valid manifest graphra.
Ez **nem általános RFC 8785 implementáció**, és nem manifest-generálási engedély.

1. Csak A1.1-valid, immutable ASCII manifest graph szerializálható ebben a flow-ban.
   Object kulcsok minden szinten növekvő ASCII byte-order szerint; locale/casefold
   nincs. Root exact sorrend: `createdAt`, `exampleOnly`, `files`, `formatId`,
   `historicalArtifacts`, `manifestId`, `pocVersion`, `schemaVersion`, `verificationMetadata`.
2. A listák sorrendje megmarad; A1.1 már ellenőrizte canonical path-orderüket.
   A serializer nem rendezi/javítja az inventoryt. Objecteknél a sorted
   reprezentáció csak comparison candidate, az input graph nem mutálódik.
3. Integer decimal, leading plus/zero/exponent és fractional part nélkül;
   boolean exact `true/false`. Manifestben null/float nincs; schema-hibával
   nem lépünk a serializerhez.
4. Stringekben quote és backslash kötelezően escaped (`\"`, `\\`), slash
   literális `/`. Elfogadott exact escape-table: backspace/tab/LF/formfeed/CR
   rövid JSON escape, egyéb control esetén lowercase `\u00xx`; printable ASCII
   nem `\u`-escaped. A tényleges valid mezőgrammar kontrollt/quote/backslash-t
   nem enged, de a serializer izolált escape-tesztje rögzíti a viselkedést.
5. Nincs insignificant whitespace, indent, BOM vagy CRLF. Pontosan egy JSON-sor
   és **egy** final LF (`0A`); az escaped LF nem raw sorváltás. UTF-8 ASCII subset.
6. Received raw bytes és serializer output length+byte equality szükséges;
   eltérés `MANIFEST_NONCANONICAL`. Nincs csendes rewrite, digest újraszámítás
   az átírt inputra vagy automatikus anchor-frissítés.

Az általános escape-helper viselkedése nem bővíti a manifest-domain-t.
Az izolált helper escape-tesztjei külön állnak a manifest acceptance tesztektől;
control/quote/backslash tartalmú izolált helper-inputból nem lesz valid manifest.

Golden-byte tervek (most nincs fixture létrehozás): GB01 egy minimális teljes
synthetic manifest hardcoded expected bytes + byteCount + independent expected
SHA-256; GB02 több, ASCII szerint rendezett file/history entry; GB03 0/max-size
integer és azonosító határok; GB04 serializer primitive escape-table; GB05 ismert
valid leap-day/whole-second UTC string. Expected bytes nem a tesztelt
serializerrel generálódnak. Negatív párjaik: root/nested key-swap, pretty space,
CRLF, missing/double LF, escaped slash, printable ASCII `\u` escape, inventory
swap, float/exponent/-0 és Unicode. Minden full-pipeline parser/canonical
negatívhoz matching **fixture** raw pin kell, különben helyesen V04 állítaná meg.

### 6.5. Külön result típusok és a 100-error budget

TD01 `DigestComparisonResult` csak digest-phase állapot: matched/mismatch,
expected/actual digest, mode és kiválasztott anchor ref; nem `ValidationResult`
és nem teljes manifest-result. TD04 `ManifestVerificationResult` külön immutable
wrapper. A meglévő M/VR/J nem módosul a terv alapján.

| Elfogadott wrapper mező                                                   | Exact jelentés / értéktartomány                                                                                                                                                                                                  |
| ------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `resultVersion`, `scope`                                                  | `1.0`, `MANIFEST_ONLY`; nem C029A teljes verification-result schema                                                                                                                                                              |
| `manifestVerdict`                                                         | `VERIFIED`, `DENIED`, `INCONCLUSIVE`, kizárólag a G01–G09 manifest-scope-ra                                                                                                                                                      |
| `trustMode`, `authenticity`                                               | trustMode FIXTURE/OPERATOR_ATTESTED vagy invalid/unsupported kérésnél null; authenticity SIMULATED/OPERATOR_ATTESTED csak igazolt local binding után, egyébként null                                                             |
| `digestStatus`, `schemaStatus`, `canonicalStatus`, `bindingStatus`        | digestStatus NOT_EVALUATED/MATCH/MISMATCH; schemaStatus NOT_EVALUATED/VALID/INVALID; canonicalStatus NOT_EVALUATED/CANONICAL/NONCANONICAL; bindingStatus NOT_EVALUATED/MATCH/MISMATCH; hard stop után a következők NOT_EVALUATED |
| `anchorId`, `approvalRef`, `manifestId`, `expectedDigest`, `actualDigest` | Csak ellenőrzött/előállított érték, különben null; nincs candidate-ből trustedként tükrözött mező                                                                                                                                |
| `primaryFailureCode`, `errors[]`, `diagnosticsTruncated`                  | Null vagy taxonómia-kód; bounded stage/path/code/message errors; explicit boolean truncation                                                                                                                                     |
| `manifest`                                                                | Csak teljes G01–G09 successnél M DTO, minden failure/unknown esetén null                                                                                                                                                         |
| `anchorHistoryStatus`                                                     | `NOT_EVALUATED` local record-history hiányakor; nem állít ledger/revocation freshness proofot                                                                                                                                    |
| `fileInventoryStatus`, `filesystemIdentityStatus`                         | Mindig `NOT_EVALUATED` ebben a subsystem-scope-ban                                                                                                                                                                               |
| `executionAuthorization`, `canLaunchPoC`                                  | Mindig `NOT_EVALUATED`, illetve `false`                                                                                                                                                                                          |

A wrapper nem helyettesíti C029A §7.2 teljes `inputVerdict/evaluation/observation`
szerződését. Későbbi adapter a teljes file/native ellenőrzés nélkül nem emelheti
`inputVerdict=VERIFIED` szintre. Nincs VM-/run-eligibility vagy indítási callback.
Nem kerül timestamp/abszolút path/raw OS exception a stabil pure resultba;
observation/writer későbbi A1.4. TD-D05 **ACCEPTED WITH MANDATORY INVARIANTS**;
az API elfogadása nem TD04 implementációs vagy CLI-integrációs engedély.

Mindkét külön immutable result típus publikus A1.2 manifest-only kimenetében
az alábbiak **kötelező invariánsok**, nem konfigurálható defaultok, caller
paraméterrel vagy factory-opcióval sem írhatók felül:

```text
scope = MANIFEST_ONLY
fileInventoryStatus = NOT_EVALUATED
filesystemIdentityStatus = NOT_EVALUATED
executionAuthorization = NOT_EVALUATED
canLaunchPoC = false
```

DigestComparisonResult MATCH csak a digest-phase sikerét jelenti: nincs kész
schema/canonical/binding proof vagy sikeres teljes Manifest DTO. Teljes
manifestVerdict=VERIFIED csak ManifestVerificationResult G01–G09 sikerében
adható, és még akkor sem full input verification, deployment authenticity,
filesystem identity, Windows security vagy transaction-execution authorization.
A1.1 ValidationResult változatlan; sikertelen A1.2 verification manifestje null.

A1.1-re egy hívás történik. A `MANIFEST_SCHEMA_INVALID` a wrapper külön
`primaryFailureCode` értéke, **nem** 101. error. V error code/path/message
változatlanul, stabil `V06_V07` stage címkével kerül át, legfeljebb100 elem;
a wrapper pontosan átviszi `diagnosticsTruncated` értékét. Nem validate-elünk
entrynként új budgettel, és nem építünk előbb teljes hibahalmazt. Más gate-ek
fail-first viselkedésűek, így nincs prior stage error-lista, amelyhez újabb100
hozzáadódna. Sort: stage/path/code/message byte-order; az A1.1 részhalmaz
path/code/message sorrendje megmarad. A truncation threshold-stopot jelent,
nem garantáltan további hibák létezését és nem globálisan smallest100-at.

## 7. Verification pipeline és precedence

C029A §7.1 V01–V14 a teljes későbbi verifieré. Az alábbi G-sorrend a tervezett
manifest-only részfolyamat. A hard gate sikertelensége után minden későbbi
lépés kimarad; nincs parse retry vagy másik anchor/mode választás.

| Gate | Parent mapping    | Művelet és stop                                                                                                                                        |
| ---- | ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| G01  | V01               | Explicit mode/context/runtime/limits/release capability validáció; deployment/strong-scope unsupportedra inconclusive, invalid configra deny/usage     |
| G02  | V02               | Candidate kiválasztás, raw anchor esetén strict decode; exact anchor validation; ACTIVE/revocation/selected tuple/profile/release binding              |
| G03  | V03               | Pure raw <=16777216 vagy későbbi bounded acquisition; túlméret MANIFEST_SIZE_EXCEEDED hash/manifest-parse előtt; egy snapshot; short/read failure stop |
| G04  | V04               | Raw SHA-256 és syntax-checked expected digest `hash_equals`; mismatch → `MANIFEST_UNTRUSTED`, C06/C08/C09 call-count0                                  |
| G05  | V05               | Strict manifest grammar/decoded-key/Unicode/integer/depth/resource guard ugyanazon raw bytes-on                                                        |
| G06  | V06–V07 pure rész | Egyetlen változatlan A1.1 validate hívás; hibára imported bounded errors, manifest=null, semmi canonical/file traversal                                |
| G07  | V06 pure rész     | Exact canonical bytes comparison a sikeresen validált graphon; mismatch deny, nincs rewrite                                                            |
| G08  | V06 binding rész  | M.manifestId/profile ID+hash összevetése anchorral/contexttel; selected release/record binding megtartása; nincs actual profile-file read              |
| G09  | V13–V14 részleges | Csak manifest-only immutable result; method return, sem writer, sem CLI-exit, sem PoC-launch                                                           |

Precedence azonos többhibás inputnál is gate-order szerinti: invalid anchor
megelőzi a manifest-readet; raw digest mismatch megelőzi a malformed JSON-t;
parser hiba megelőzi a schema-hibát; schema/path hiba megelőzi a noncanonical
byte-hibát; canonical eltérés megelőzi a decoded manifest binding hibáját.
Az identity/profile candidate→selection eltérései már G02-ben megállnak;
manifest metadata→anchor eltérései csak G08-ban ellenőrizhetők.

Két explicit finomítás, nem elrejtett forráskonfliktus:

- C028 §4.4.1 korábbi parse-first megfogalmazása helyett C029A V04–V05 és a
  jelen A1.2 feladat kifejezett digest-before-parse követelménye az irányadó.
  A régi dokumentum nem módosul. E sorrend nem nyitott alternatíva e feladatban.
- A1.1 fizikailag egy hívásban végzi a V06/V07 pure szabályokat, ezért G06–G08
  combined pure gate-csoport. C029A eredeti canonical-before-path sorrendjének
  apró finomítása a változatlan A1.1 újrafelhasználását teszi lehetővé, FS-hívás
  nélkül. E failure precedence és parent mapping **TD-D02 ACCEPTED** a mostani
  owner-döntéssel; a parent V01–V14-re mutató táblázat ezt rögzíti. Ez nem
  ténylegesen megvalósult integráció és nem módosítja C028-at.

V08–V12, tényleges input-tree enumeration/content/history/profile hash és native
proof nem fut ebben a tervben, és nem jelölhető implicit PASS-nak az A1.2-ben.

## 8. Önállóan review-zható implementációs szeletek

Az alábbiak **javasolt jövőbeli fájlok**, nem mostani allowlist. Minden út a
`tests/ProofOfConcept/RmaTransactionBoundary/integrity/` alá értendő. Közös
engedhető integration edits: saját `tests/run-tests.php` explicit require-mapje
és `README.md` tényszerű frissítése, csak az adott slice külön engedélyében.
Meglévő M/V/VR/J/T változtatása alapértelmezetten nincs a javasolt scope-ban.

### TD01 — Pure anchor és digest core

- Objective: C02–05, strict context binding és scoped digest-result, fájlrendszer nélkül.
- Exact proposed new files: `src/TrustAnchor.php`, `src/TrustContext.php`,
  `src/AnchorProvider.php`, `src/AnchorValidator.php`, `src/TrustedDigestVerifier.php`,
  `src/DigestComparisonResult.php`, `tests/TrustVerificationTest.php`.
- Dependency: A1.1 accepted baseline, D2 és TD-D06 szerint elfogadott §6.1 selection/mode szerződés; külön TD01 authorization szükséges.
- AC: unknown/type/version/revoked/missing/substitution fail closed; max16MiB
  raw comparison; mismatch után semmilyen manifest parserhívás; explicit local authenticity.
- Positive: known SHA pair, injected fixture selection, independently attested tuple.
- Negative: candidate ID/hash/profile/release swap, revoked/missing, bad digest syntax,
  file+manifest csere fix pin mellett, unsupported deployment request.
- Limitation: nincs parser/canonical/full manifest success, protected store vagy ledger proof.
- Stop: context hiányos/önbevallásos, silent trust fallback, release mismatch vagy scope-bővítés.

### TD02 — Bounded strict raw parser, külön előfeltétel

- Objective: C06–07; duplicate-key/Unicode/numeric/depth és allocation guard.
- Exact proposed new files: `src/StrictJsonTokenizer.php`, `src/JsonParseResult.php`,
  `tests/StrictJsonTest.php`.
- Dependency: §6.3/TD-D04 elfogadott wire-domain/resource contract; külön TD02 implementációs engedély szükséges. TD01 csak a későbbi integrated orderinghez kell; runtime capacity acceptance a megvalósítás utáni külön kapu.
- AC: stdClass/list preservation, decoded key guard overwrite előtt, float fallback0,
  limits allokáció előtt, minden valid A1.2 wire input megőrzése, maximum-valid-wire és invalid-input memóriaigazolás, fail-first deterministic error. Standalone parser nem authenticity API.
- Positive: exact integer grammar, escaped ASCII, distinct-object same key, depth16.
- Negative: duplicate escaped key, depth17, overflow, invalid UTF-8/surrogate,
  trailing comma/comments, enormous unknown graph/string és cap+1.
- Limitation: parser helyessége nem schema/provenance proof; field contract A1.1-é.
- Stop: duplicate-key guardot csak decode után lehetne elhelyezni; valid-domain limit
  indokolatlanul szűkül; erőforráspróba fatal; dependency-install igény külön review nélkül.

### TD03 — Canonical JSON serializer és exact comparison

- Objective: C08 és golden byte-contract freeze, a received raw input módosítása nélkül.
- Exact proposed new files: `src/CanonicalJson.php`, `tests/CanonicalJsonTest.php`.
- Dependency: TD02 decoded representation az integrált flow-hoz, A1.1-valid domain, TD-D03 accepted contract; külön TD03 authorization szükséges.
- AC: §6.4 minden byte-szabálya, bounded output, input immutable, exact received equality.
- Positive: GB01–GB05 hardcoded golden recipes megvalósítása későbbi tesztben.
- Negative: egyetlen space/LF/CRLF/escape/key-order eltérés, inventory swap,
  noncanonical input rewrite-kísérlet tiltása.
- Limitation: nem RFC8785, nem actual manifest generator és nem digest-before-parse kivétel.
- Stop: két serializer vagy platform eltérő canonical outputot ad; unknown escaping;
  már jóváhagyott raw pin automatikus „javítása” szükségesnek látszik.

### TD04 — Manifest-only orchestrator és result

- Objective: G01–G09, C09 egyszeri újrafelhasználása és §6.5 bounded result.
- Exact proposed new files: `src/ManifestDigestVerifier.php`,
  `src/ManifestVerificationResult.php`, `tests/ManifestVerificationTest.php`.
- Dependency: TD01–03 későbbi implementációjának elfogadása; TD-D02/TD-D05 már elfogadott; külön TD04 authorization szükséges.
- AC: no parse on mismatch, ugyanaz a raw snapshot minden szakaszban,
  max100 diagnostics, ugyanazon instance repeated-use helyes, no partial DTO,
  programming exceptions továbbterjednek, canLaunchPoC minden ágon false.
- Positive: matching fixture digest + canonical valid M, operator-attributed scope,
  valid→truncated→valid→below-budget-invalid→valid lifecycle.
- Negative: multi-failure precedence, metadata mismatch, malformed canonical/schema,
  R1 99/100/101 és100000 malformed entries, internal exception, full-result promotion.
- Limitation: `MANIFEST_ONLY`, file/native/identity-history nincs full proofként jelölve.
- Stop: A1.1 weakening/per-entry budget reset vagy wrapper101st error kellene;
  silent exception→success; eltérés az elfogadott canonical/parent mappingtől.

### TD05 — Bounded acquisition, A1.3-hoz kapcsolódó külön scope

- Objective: C01 adapter read-only local regular-file inputhoz, no second read.
- Exact proposed new files: `src/ManifestByteSource.php`, `src/ManifestSnapshot.php`,
  `src/ManifestSnapshotReader.php`, `tests/ManifestAcquisitionTest.php`.
- Dependency: TD04 immutable bytes interface; explicit own-root/file I/O test approval;
  A1.3 native assurance policy. Nem prerequisite a TD01 pure core-hoz.
- AC: cap+1 bounded read, partial/error/EOF semantics, handle close minden ágon,
  one source-open, no URL/CWD fallback, requested native scope fail closed.
- Positive: D saját regular file ugyanazt a bytes-t adja, mint a pure string API;
  injected short chunks összerakása valódi EOF-ig.
- Negative: premature EOF/false read/zero progress/oversize/stat drift,
  második-read call-count0, unsupported native request és explicit source mismatch.
- Limitation: `CONTENT_ONLY`, nem atomi tree/native identity/race proof.
- Stop: privilege/ACL/link/VM/network igény, approved root bizonytalanság, implicit
  app/config/fallback source vagy writer. Ambiguous saját fixture retained, nincs broad cleanup.

### TD06 — Integration contract és negatív consumer proof

- Objective: a részleges A1.2 és a későbbi teljes A1 verifier közötti boundary rögzítése.
- Exact proposed new file: `INTEGRATION.md`; proposed modifications:
  `tests/ManifestVerificationTest.php`, `README.md`, szükség szerint saját runner.
- Dependency: TD04 accepted result; TD05 csak file-backed use-hoz; full Windows
  consumerhez A1.3–A1.6 és C028 külön integráció szükséges.
- AC: fixture/operator result nem válik deployment approvalvá; history/current
  külön; no launch import; old203 plusz új tesztlista konkrét eredménnyel átadva.
- Positive: explicit scope mapping és selected release/profile binding megőrzése.
- Negative: stale/missing ledger/native proof, SIMULATED vagy CONTENT_ONLY resultból
  execution eligibility, legacy history mint current manifest és source import.
- Limitation: nincs actual VM/bootstrap/launcher/result-writer implementáció.
- Stop: L/R/P/W/S/H/app/ADR változtatást vagy futtatást igénylő integration;
  ezt új feladatba kell emelni, nem TD06 alatt elvégezni.

TD01 → TD04; TD02 → TD03 → TD04; TD04 → TD05/TD06. Ez aciklikus.
A native/deployment hiánya nem blokkolja a pure tervek review-ját, viszont
nem hagyható ki olyan future successből, amely ténylegesen ezt az assurance-t kéri.
Rollback minden slice-nál az új API/adapter használatának deaktiválása;
nincs anchor auto-update, történeti fájl-helyreállítás vagy evidence törlés.

## 9. Standalone tesztmátrix — PLANNED / NOT EXECUTED

P = pure/injected, tartós output nélkül. D = később külön engedélyezett saját
disposable local filesystem. F = külön PHP folyamat, csak subsystem teszt;
nem P/R/W/L vagy Laravel. Közös assertion: structured failure vagy explicit
programming exception, sem launch, sem MySQL/network/app bootstrap; input immutable.
Parser/schema/canonical negatívoknál matching fixture raw digestet kell injektálni.
TD01 direct digest unit tests nem igényelnek parserhívást vagy canonical manifestet.

| Test ID | Setup / támadás                                                                               | Expected assertion                                                                                                                     | Típus / slice                    |
| ------- | --------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------- |
| TD-T01  | Known-answer raw SHA, egyező digest                                                           | MATCH, exact expected/actual, SIMULATED                                                                                                | P / TD01                         |
| TD-T02  | Egy byte eltér, pin változatlan                                                               | MANIFEST_UNTRUSTED, parser/schema/canonical calls0                                                                                     | P / TD01/04                      |
| TD-T03  | File+manifest együtt csere, external tuple marad                                              | Manifest digest mismatch; új file hash nem menti fel                                                                                   | P / TD01/04                      |
| TD-T04  | Anchor hiányzik                                                                               | ANCHOR_MISSING; manifest acquisition/parse0                                                                                            | P / TD01                         |
| TD-T05  | Mind a9 field missing/null/wrong type, extra field                                            | ANCHOR_INVALID, nincs coercion/default                                                                                                 | P / TD01                         |
| TD-T06  | anchorVersion2, unknown status, lowercase active                                              | ANCHOR_VERSION_UNSUPPORTED / ANCHOR_INVALID; nincs fallback                                                                            | P / TD01                         |
| TD-T07  | Selected record ACTIVE, candidate REVOKED vagy stale record                                   | ANCHOR_REVOKED deny; freshest-status proof hiánya explicit, nem frissnek nyilvánítva                                                   | P / TD01/06                      |
| TD-T08  | Candidate anchor/digest/approvalRef cseréje                                                   | ANCHOR_SELECTION_MISMATCH fixed independent tuple mellett                                                                              | P / TD01                         |
| TD-T09  | Matching raw pin, más decoded manifestId                                                      | ANCHOR_MANIFEST_MISMATCH, manifest=null                                                                                                | P / TD04                         |
| TD-T10  | Selected/candidate/manifest profile ID vagy hash eltér                                        | PROFILE_MISMATCH a megfelelő gate-ben; nincs profile-file-read claim                                                                   | P / TD01/04                      |
| TD-T11  | Candidate verifierReleaseId más                                                               | VERIFIER_RELEASE_MISMATCH; nem self-selected release                                                                                   | P / TD01                         |
| TD-T12  | Ugyanaz selected manifestId, más candidate digest; másik approval története nem adott         | Local binding deny; globális reuse ledger NOT_EVALUATED                                                                                | P / TD01/06                      |
| TD-T13  | Invalid SHA: uppercase/63/65/nonhex/newline/string-coercion                                   | ANCHOR_INVALID/context error, comparison nem laza                                                                                      | P / TD01                         |
| TD-T14  | FIXTURE, operator, deployment request, invalid mode                                           | Exact authenticity; deployment CAPABILITY_UNAVAILABLE; no mode promotion                                                               | P / TD01/04                      |
| TD-T15  | raw length cap-1/cap/cap+1                                                                    | Első kettő size gate-en átmegy; cap+1 stop hash/parse előtt, nem full-valid állítás                                                    | P+D / TD01/05                    |
| TD-T16  | Rövid chunkok, EOF, false/empty-progress/premature EOF                                        | Chunk short-read kezelve; hiba esetén partial bytes nem verify-olhatók                                                                 | P+D / TD05                       |
| TD-T17  | Source két külön tartalmat adna két read-open esetén                                          | Egy source-open/snapshot, hash+parse ugyanaz; reopen count0                                                                            | P+D / TD05                       |
| TD-T18  | Path/URL/CWD/env fallback, requested Windows identity adapter nélkül                          | CONFIG_INVALID / CAPABILITY_UNAVAILABLE, nincs source/network probe                                                                    | P / TD05/06                      |
| TD-T19  | Raw mismatch és malformed JSON egyszerre                                                      | MANIFEST_UNTRUSTED, JSON parse call-count0                                                                                             | P / TD04                         |
| TD-T20  | Truncated JSON, garbage suffix, comments, trailing comma                                      | JSON_INVALID, nincs schema/canonical                                                                                                   | P / TD02/04                      |
| TD-T21  | Direct és nested duplicate decoded key; escaped-key pár                                       | JSON_DUPLICATE_KEY overwrite előtt                                                                                                     | P / TD02                         |
| TD-T22  | Azonos key külön objectben, numeric-looking distinct keys, case-eltérő key                    | Parser pontos equality; nem hamis duplicate, unknown mező később reject; decoded NUL/control key JSON_KEY_INVALID property-write előtt | P / TD02                         |
| TD-T23  | BOM, invalid UTF-8, overlong sequence, lone surrogate                                         | ENCODING_INVALID; nincs repair                                                                                                         | P / TD02                         |
| TD-T24  | Valid non-ASCII NFC/NFD és surrogate pair                                                     | JSON_NON_ASCII domain-deny, nem normalizált acceptance                                                                                 | P / TD02                         |
| TD-T25  | 0/intmax/intmax+1/-0/negative/fraction/exponent/leading zero                                  | Cast előtti overflow reject; size-domain2GiB A1.1; nincs float fallback                                                                | P / TD02/04                      |
| TD-T26  | Depth16/17, huge unknown object/string/array graph                                            | §6.3 formális bound és feloldott wire-domain szerinti allocation guard; maximum-valid memória acceptance később                        | P+F / TD02                       |
| TD-T27  | Raw anchor escaped duplicate key, unknown field, limit overflow                               | Anchor strict delivery gate deny; manifest parse továbbra0                                                                             | P / TD01/02                      |
| TD-T28  | GB01–GB05 positive golden recipes                                                             | Independent hardcoded bytes/byteCount/SHA oracle; GB04 helper-teszt külön a manifest acceptance-től                                    | P / TD03                         |
| TD-T29  | Space/CRLF/BOM/missing LF/double LF/key-swap/slash escape                                     | Noncanonical vagy earlier encoding reject; nincs rewrite                                                                               | P / TD03/04                      |
| TD-T30  | Inventory order/case/cross-list duplicate és unknown schema                                   | A1.1 exact code import, max100, nincs FS traversal                                                                                     | P / TD04                         |
| TD-T31  | A1.1-T01 timestamp valid/leap-invalid/fraction/offset                                         | A1.1 továbbra változatlanul dönt, nincs új date-parser                                                                                 | P / TD04                         |
| TD-T32  | 99/100/101 diagnostic, több inventory/stage                                                   | Max100 összesen; truncation false/true/true; nincs101st summary error                                                                  | P / TD04                         |
| TD-T33  | R1:99999 malformed files +1 malformed history,128MiB                                          | Exit0, invalid/null,100 diagnostics, truncation true, peak memory közölve                                                              | F / TD04                         |
| TD-T34  | Ugyanazon instance valid→100 errors→valid→below threshold→valid                               | Nincs stale errors/truncation/DTO/state                                                                                                | P / TD04                         |
| TD-T35  | Repeated input/property-order változtatás                                                     | Stable result és bounded subset sorrend; input serialize/hash unchanged                                                                | P / TD04                         |
| TD-T36  | Nem termination-signal exception a helperből                                                  | Pure API továbbengedi; outer boundary később INTERNAL_ERROR, sosem success                                                             | P+F / TD04                       |
| TD-T37  | Fake source/provider/writer spies + include-map inspection                                    | Pure core FS/network/process/DB/writer calls0, Laravel/P/R/W imports0                                                                  | P / összes                       |
| TD-T38  | H-shape current inputként, illetve history synthetic copy byte-mutation                       | Current shape reject; actual H unchanged, történeti pin nem új current trust                                                           | P+D / TD06; content check A1.3/6 |
| TD-T39  | Consumer full input verification/VM authorizationt kér local digest-resultból                 | Elutasítás; canLaunchPoC=false és NOT_EVALUATED megmarad                                                                               | P / TD06                         |
| TD-T40  | Teljes203 eredeti cases + új focused catalog és szándékos assertion failure külön folyamatban | Eredetiek zöldek, runner nonzero-t propagál; tényleges új darabszám közölve                                                            | P+F / minden implementált slice  |

D tesztekben csak synthetic saját `payload/` és diszjunkt `control/` root,
pontos fixture ownership registry, handles close és exact saját fixture cleanup.
Nincs actual H vagy WAMP adat másolási/írási cél, nincs ACL/link létrehozási
követelmény. Ambiguous identity esetén retained report/STOP, nem recursive
idegen target delete. F teszt csak approved PHP és saját explicit entrypoint;
nincs launcher/shellből generált parancs, persistent output/coverage/cache.
Fixture PASS nem production trust, native Windows vagy absence-of-attack proof.

### 9.1. Wire-domain határok — további PLANNED / NOT EXECUTED esetek

Az eredeti TD-T01–40 mátrix megmarad; WD01–07 annak TD-T15/26/33
határ- és resource-eseteit pontosítja. Nincs most fixture vagy futtatás.
Full acceptance pozitív esethez independent FIXTURE context, matching raw pin,
anchor és profile/manifest binding is kell; A1.1-valid graph önmagában nem PASS.

| ID   | Tervezett bemenet / bizonyíték                                                                                              | Elvárt eredmény                                                                                                                                      |
| ---- | --------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| WD01 | A1.1-valid, strict és canonical, <=16MiB, minden independent binding valid                                                  | MANIFEST_ONLY VERIFIED; változatlan öt result-invariant                                                                                              |
| WD02 | §6.3.2 szerinti A1.1-valid canonical44100623-bájtos konstrukció                                                             | A1.1 domain-valid, A1.2 MANIFEST_SIZE_EXCEEDED; nincs automatikus split/rewrite                                                                      |
| WD03 | Alább levezetett pontosan16777216-bájtos canonical, A1.1-valid bemenet, matching fixture trust                              | G03 átenged; teljes G01–G09 success a bindingokkal; a final LF is beleszámít                                                                         |
| WD04 | WD03 egyetlen legal provenance karakterrel hosszabb változata:16777217 bájt                                                 | MANIFEST_SIZE_EXCEEDED, nem schema/canonical/digest mismatch                                                                                         |
| WD05 | WD02/WD04 + digest spy, valid G01/G02 feltételek                                                                            | SHA-256 invocation-count0; nincs actual digest előállítás                                                                                            |
| WD06 | WD02/WD04 + parser/schema/canonical spies, valid G01/G02 feltételek                                                         | Manifest parser/decode/A1.1/canonical invocation-count0; anchor korábbi strict parse külön művelet                                                   |
| WD07 | Maximum-valid A1.2 wire-domain resource-extremumok külön F folyamatban; snapshot/decoded/seen/canonical/result együtt élése | Actual peak-memory és runtime limit rögzítve; minden valid wire inputot megőrző guard, controlled deterministic invalid failure; fatal/OOM nem siker |

WD03/04 reprodukálható **jövőbeli** fixture-receptje, most csak számtan:
§6.3.2 nulla size-os családja F=1, H=38042, history i=0..38041;
a teljes maximum-hosszú stringekkel L=1064+441×38042=16777586.
A root manifestId és pocVersion, valamint a metadata profileId és provenanceRef
hosszát80-ról1-re csökkentjük (ugyanazon betű), összesen316 bájttal;
a legelső history provenanceRef-jét80-ról26-ra, további54 bájttal.
Így L=16777586-316-54=16777216. Minden ID nemüres és legális,
N=38043; path-order/uniqueness/case/timestamp/size/canonical szabály megmarad.
Ugyanezen history provenanceRef27 karakterrel L=16777217 (WD04).
A trust fixture selected tuple-ja az új rövid ID-khoz igazítandó; ez csak későbbi
explicit test authorization alatt létrehozható synthetic fixture, nem actual anchor.

WD07 nem pusztán egy byte-boundary minta: külön keresendő/mérendő a wire-domainen
belül legnagyobb entry/node/seen-set terhelés és a byte-heavy eset, köztük WD03.
A raw16MiB és derived N100000 maximumok egyidejű elérhetőségét külön
vizsgálni kell; itt nem feltételezzük azt. Invalid-input stress és R1 malformed100000-entry128MiB regresszió
külön követelmény marad (§6.3.3); sem a határrecept, sem az objektumszám nem
helyettesít tényleges peak-memory mérést. Ezek most nem végrehajtott tesztek.

## 10. Failure taxonómia és determinisztikus eredmény

Az alábbi reason code-ok az elfogadott TD-D05 result API tervezett részletezései. C029A §7.3
elfogadott kategóriáit nem cseréljük le; method returnnek nincs OS exitje.
Az exit oszlop a **későbbi** CLI mapping, nem mostani futtatás.

| Reason / primary failure                                                               | Gate                         | Eredmény                                                          | Parent mapping / future exit                                                     |
| -------------------------------------------------------------------------------------- | ---------------------------- | ----------------------------------------------------------------- | -------------------------------------------------------------------------------- |
| CONFIG_INVALID                                                                         | G01                          | DENIED, usage fault                                               | V01 /64                                                                          |
| CAPABILITY_UNAVAILABLE                                                                 | G01/G03                      | INCONCLUSIVE, nincs silent downgrade                              | V01/V03 /30                                                                      |
| ANCHOR_MISSING                                                                         | G02                          | DENIED, no manifest read                                          | ANCHOR_MISSING /20                                                               |
| ANCHOR_INVALID                                                                         | G02                          | DENIED, type/required/unknown/hash/status format                  | ANCHOR_INVALID /20                                                               |
| ANCHOR_VERSION_UNSUPPORTED                                                             | G02                          | DENIED, ismert rossz wire-version                                 | Invalid anchor család /20                                                        |
| ANCHOR_REVOKED                                                                         | G02                          | DENIED                                                            | Invalid/untrusted anchor család /20                                              |
| ANCHOR_SELECTION_MISMATCH                                                              | G02                          | DENIED, substitution/fixed ID→digest binding sérült               | Invalid/untrusted anchor /20                                                     |
| PROFILE_MISMATCH, VERIFIER_RELEASE_MISMATCH                                            | G02/G08                      | DENIED, a selected/candidate vagy decoded binding hibás           | C028 PROFILE_MISMATCH/VERIFIER_UNVERIFIED, C029A untrusted /20                   |
| MANIFEST_SIZE_EXCEEDED                                                                 | G03                          | DENIED                                                            | LIMIT_EXCEEDED raw-manifest részletezése /20                                     |
| IO_READ_FAILED                                                                         | G03                          | INCONCLUSIVE, nincs feltételezett mismatch/PASS                   | V03 /30                                                                          |
| MANIFEST_UNTRUSTED                                                                     | G04                          | DENIED                                                            | C029A V04 /20; nem inventory HASH_MISMATCH/10                                    |
| JSON_INVALID, JSON_DUPLICATE_KEY                                                       | G05                          | DENIED                                                            | C029A V05 /20                                                                    |
| ENCODING_INVALID, JSON_NON_ASCII, JSON_KEY_INVALID                                     | G05                          | DENIED, hibás encoding/domain külön                               | C029A V05 /20                                                                    |
| JSON_NUMBER_INVALID, JSON_INTEGER_OVERFLOW, JSON_DEPTH_EXCEEDED, JSON_COMPLEXITY_LIMIT | G05                          | DENIED, cast/float/allokáció előtt                                | C029A type/limit család /20                                                      |
| MANIFEST_SCHEMA_INVALID                                                                | G06                          | DENIED; külön primary code, V eredeti errors változatlanok        | V06–V07; path/collision/order security error→10, egyéb schema→20                 |
| MANIFEST_NONCANONICAL                                                                  | G07                          | DENIED, nincs automatic repair                                    | C029A V06 /20                                                                    |
| ANCHOR_MANIFEST_MISMATCH                                                               | G08                          | DENIED                                                            | C029A V06 untrusted binding /20                                                  |
| INTERNAL_ERROR                                                                         | Minden gate külső boundaryja | INCONCLUSIVE/partial; pure API programming exception továbbterjed | Required proof unavailable /30; a későbbi CLI-implementáció külön engedélyezendő |

G06 több hibánál a future exit-prioritás C029A §7.1 szerint: invalid/untrusted
schema20 megelőzi integrity/security10-et. Csak path/collision/order hibák
esetén10; schema-hiba is jelen van →20. A truncation miatt hiányzó további
okok létezését nem találjuk ki. A stable result primaryFailureCode marad
MANIFEST_SCHEMA_INVALID, az eredeti error code-ok nem kerülnek átnevezésre.
Legacy H forma G06-ban invalid schema; a későbbi teljes verifier
`LEGACY_HISTORY_FORMAT` speciális diagnózisa nem meglévő A1.1 képesség.

Parser fail-first offset-order, anchor fixed field-order, G06 bounded A1.1
subset, wrapper stage/path/code/message sort. Azonos raw/context/anchor/runtime
policy mellett az eredmény azonos. Time/OS message/provenance retrieval timing
nem kerül a stable evaluationbe. Error nem idéz raw payloadot/secretet.

Pure orchestration nem catch-el általános Throwable-t és nem nyeli el V
programhibáit. Csak V saját dedicated budget signalja válik invalid resulttá
az ottani boundaryn. Későbbi CLI legkülső exception boundaryja safe
INTERNAL_ERROR/INCONCLUSIVE-t adhat; stack/raw message csak protected observation,
writer/crash hibánál nonzero, soha fabricated structured VERIFIED.

## 11. Biztonsági korlátok és megőrzés

- Integrity/authenticity/provenance/authorization külön fogalom. Egy approved
  digest csak azt rögzíti, mely bájtokat vártuk; rossz approved tartalom ettől
  még hibás lehet, ezért szükséges parser/schema/canonical/binding és később inventory.
- Local FIXTURE/OPERATOR_ATTESTED nem védi meg a verifiert/contextet/anchorját
  same-user támadótól. Az ACTIVE és approvalRef nem signature, nincs hidden signing service.
- Raw snapshot nem atomi tree proof. Windows reparse/hardlink/ADS/file-ID,
  privileged actor és verify/use race D3/A1.3/C028 külön natív/provisioning határa.
- A mód vagy wrapper sikeréből nincs B2 token-, C2 izoláció-, MySQL instance-
  vagy transaction-proof. Execution authorization minden resultban NOT_EVALUATED.
- D4 szerint pure core nem ír evidenciát; output-retention/checked writer A1.4
  külön feladat. Nincs automatic trusted-anchor creation, approval, update/revocation repair.
- H exact raw hash-e változatlan marad (§2.2). Történeti FAIL/BLOCKED/provenance/
  source-hash mezőket nem írunk át; aktuális source drift nem history-hiba javítása.
- Az L/R existence stop és P vendor bootstrap ma is fennáll. A1.2 egyiket sem
  hívhatja meg; trusted pre-autoload és history/output adapter csak külön integráció.

## 12. Döntés- és konfliktusregiszter

**ACCEPTED**: aktuális felhasználói feladat + owner acceptance szerint A1/B2/C2,
D1 camelCase, D2 independent explicit anchor, D3 fail-closed külön native adapter,
D4 no automatic persistent evidence, A1.1-T01 UTC whole seconds és R1 max100
azonnali controlled termination. Ezek nem új választási kérdések.

A korábbi TD-D02/03/05/06 owner-döntés forrása: **C3-02.9-A1.2 Architecture
Decision Finalization**, §3–5. TD-D04 véglegesítésének forrása a **TD-D04
Architecture Decision Finalization** feladat §3–6/§9, Option A explicit approval.
ACCEPTED architectural contractot jelöl, nem runtime proofot vagy implementációs
felhatalmazást. ACCEPTED WITH CLARIFICATION a domain-pontosítással érvényes;
ACCEPTED WITH MANDATORY INVARIANTS az öt konstans result-korláttal.
ACCEPTED WITH EXPLICIT WIRE-DOMAIN RESTRICTION a változatlan A1.1 és az
extra raw16MiB acceptance feltétel különválasztása. DEFERRED külön későbbi
architectural döntés és implementációs engedély.

| Decision ID / státusz                                 | Exact source references                                                                                | Rögzített döntés / security határ                                                                                                                                                                                                                        | Megmaradó gate                                                                                                                                        |
| ----------------------------------------------------- | ------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| TD-D01 ACCEPTED scope precedence                      | C028 §4.4.1; C029A §7.1 V04–05; korábbi A1.2 feladat §6; jelen owner §3 TD-D02                         | Digest-before-manifest-parse; anchor strict decode külön korábban lehet                                                                                                                                                                                  | C028 változatlan, nincs végrehajtás                                                                                                                   |
| TD-D02 ACCEPTED                                       | C029A §12/§7.1 V06–07; RD parser-deferral; V39–163; jelen owner §3 TD-D02                              | Külön TD02 parser/TD03 canonicalizer; A1.1 változatlan; G06 schema/path, G07 canonical, G08 decoded binding és meglevő failure precedence; parent V01–V14 mapping §7                                                                                     | TD01–TD06 nincs automatikusan engedélyezve                                                                                                            |
| TD-D03 ACCEPTED WITH CLARIFICATION                    | C029A §4.2; jelen owner §3 TD-D03; e terv §6.4                                                         | Exact canonical bytes elfogadott kizárólag A1.1-valid graphra; helper escape-tesztek külön, independent golden oracles, nem RFC8785                                                                                                                      | TD03 saját future implementation authorization                                                                                                        |
| TD-D04 ACCEPTED WITH EXPLICIT WIRE-DOMAIN RESTRICTION | C029A §4.1/4.2/14 OD-A05; V20–24 és39–163; TD-D04 owner feladat §3–6/§9 Option A; e terv §6.3.1–3/§9.1 | A1.1 változatlan; A1.2 strict/canonical/A1.1-valid/independent-bound és eredeti raw <=16777216; túlméret MANIFEST_SIZE_EXCEEDED hash/parse előtt. N+4 container,4N+10 scalar,4N+13 member, per-object9/value240/key20; canonical max44584955 bizonyított | TD02 NOT AUTHORIZED; future maximum-valid-wire/invalid-input memória és R1 128MiB acceptance kötelező; nincs automatikus implementation authorization |
| TD-D05 ACCEPTED WITH MANDATORY INVARIANTS             | C029A §7.1–7.3/§12 A1.4; VR9–55; jelen owner §3 TD-D05; e terv §6.5/§10                                | Külön immutable result types, változatlan VR; öt fix invariant; null unsuccessful manifest; max100 és exact truncation; no101st marker; programming exception propagation                                                                                | TD04 és future CLI/integration saját engedély; VERIFIED csak MANIFEST_ONLY                                                                            |
| TD-D06 ACCEPTED                                       | C029A §5.1–5.2/14 OD-A02; jelen owner §3 TD-D06; e terv §4/§6.1                                        | Independent selected tuple/profile/release; FIXTURE/SIMULATED és OPERATOR_ATTESTED külön; candidate/env/CWD selection és promotion tiltott; ACTIVE nem provenance                                                                                        | TD01 NOT AUTHORIZED; same-user adversary ellen nincs local garancia                                                                                   |
| TD-D07 DEFERRED                                       | C028 §4.3/13 OD-TRUST; C029A §5.2/11; jelen owner §3 TD-D07                                            | Protected store/bootstrap/signing, revocation freshness service, global manifest-ID ledger, production trust promotion nincs implementálva                                                                                                               | Külön architectural decision + implementation authorization; unsupported deployment fail closed                                                       |
| TD-D08 DETAIL snapshot mechanism                      | C029A §7.1 V03–04/§6.2; korábbi A1.2 feladat §6                                                        | Same-byte immutable snapshot, nincs hash/parse reopen; pure és filesystem-backed acquisition külön                                                                                                                                                       | TD05 külön filesystem I/O authorization, capability review                                                                                            |
| TD-D09 DEFERRED inventory/native/CLI                  | C029A §6–8/12 A1.3–A1.6; C028 §10; korábbi A1.2 feladat §13                                            | Teljes inventory/native/output/VM consumer külön; partial resultból nincs teljes execution eligibility                                                                                                                                                   | Külön integration/platform/security authorization                                                                                                     |

TD-D02/03/05/06 elfogadása megmarad; TD-D04 korábbi feltételes/raw-domain
blokkja Option A explicit jóváhagyásával lezárt. A matematikai deriváció és a
konstrukció igazolt, runtime proof továbbra nincs. A1/B2/C2, D1–D4 és A1.1-T01
nem nyílik újra; A1.1 és a történeti specifikációk változatlanok.

## 13. Acceptance criteria és dokumentációs validáció

### 13.1. Következő implementáció mérhető AC-jei — NOT EXECUTED

| AC      | Required evidence                                                                                                                                                                                                                    |
| ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| TD-AC01 | Exact nine-field anchor + independent selection + local mode tests; missing/revoked/version/substitution/profile/release minden ág fail closed                                                                                       |
| TD-AC02 | Eredeti raw <=16777216 és single snapshot; WD01–06 domain/exact-cap/cap+1; túlméreten hash/manifest-parse0 MANIFEST_SIZE_EXCEEDED; SHA known answer; constant-time primitive inspected; mismatchen parser/schema/canonical0          |
| TD-AC03 | Strict duplicate/Unicode/BOM/surrogate/integer/depth/derived guard allokáció előtt, valid A1.2 wire-domain nem szűkül; WD07 actual max-valid-wire és invalid-input peak-memory; nincs fatal/OOM success vagy json_decode-only bypass |
| TD-AC04 | Canonical exact key/list/number/escape/oneLF bytes; byte-eltérés deny, input és expected pin unchanged                                                                                                                               |
| TD-AC05 | Egy A1.1 call, eredeti203 teszt zöld; max100 global diagnostic, exact99/100/101, R1 separate128M exit0/peak-memory evidence                                                                                                          |
| TD-AC06 | Repeated instance lifecycle/input immutability és unrelated exception propagation; nincs stale/partial successful DTO                                                                                                                |
| TD-AC07 | Scoped immutable result, deterministic precedence/order, truncation megőrzés; no execution authorization és no deployment promotion                                                                                                  |
| TD-AC08 | Pure no-I/O/no-app/no-DB/no-PoC import/call evidence; D reader külön authorized saját fixture és handles/cleanup proof                                                                                                               |
| TD-AC09 | H és meglévő source byte-hash-ek megőrzése; nincs launcher guard removal vagy régi hash/provenance átírás                                                                                                                            |
| TD-AC10 | Exact allowed diff, syntax/Pint/standalone/format/JSON docs check tényleges eredménnyel, kihagyott/blocked kapuk indokkal; owner review, nem self-approval                                                                           |

### 13.2. Jelen documentation-only feladat validációs határa

Releváns DoD-típus: dokumentáció, security/testinfra csak elemzés tárgya.
Egyetlen meglévő untracked Markdown módosítása engedélyezett. Ellenőrizendő a teljes dokumentum,
14 főfejezet, forráslinkek és sor/fejezet referenciák, a §6.3 formális resource-modell, tíz komponens,
TD01–TD06, TD-T01–40 és WD01–07, döntésregiszter és A1.1/C029A consistency.
Lokális Markdown Prettier, UTF-8/fence/whitespace, `git diff --check` és az
untracked új file külön ellenőrzése szükséges; a 945 tracked file hash-egyezése
és Git scope a záró átadás tényleges bizonyítéka. A checkek eredményét az
átadási jelentés közli, nem tekintjük előre végrehajtottnak ezt a felsorolást.

Nem fut PHP/SQL/PoC teszt, Pint, PHPStan, Laravel/Composer/full backend suite,
frontend build vagy VM/native probe: nincs executable módosítás és e feladat
runtime teszteket tilt. A korábbi203-as passing baseline nem mai új futás.
AGENTS/README/steering/ADR/knowledge/playbook/workflow/memory/backlog/i18n
módosítás N/A: a feladat pontosan ezt az egy tervet engedi módosítani.
Az owner által explicit elfogadott Option A resource-domain döntést és a
többi változatlan contractot itt rögzítettük. Product/business működés és implementáció nem változott.

## 14. Implementation authorization gate és átadás

**PLAN COMPLETE — IMPLEMENTATION APPROVAL REQUIRED.**

A dokumentációs architectural döntések rögzítése nem ad végrehajtási jogot.
A következő slice-gate-ek kötelezők:

| Slice | Mostani gate       | Feloldás                                                                                                                                                |
| ----- | ------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| TD01  | **NOT AUTHORIZED** | Új explicit implementációs feladat exact baseline-nal/allowlisttel; TD-D06 contract már elfogadott, nem kell újraválasztani                             |
| TD02  | **NOT AUTHORIZED** | TD-D04 resource/wire-domain contract elfogadott; saját explicit implementációs engedély, majd maximum-valid-wire/invalid-input memória és R1 acceptance |
| TD03  | **NOT AUTHORIZED** | TD-D03 elfogadott domain/escape clarification betartása és saját future implementation authorization; integrated dependencies nem kerülhetők meg        |
| TD04  | **NOT AUTHORIZED** | TD01–03 actual acceptance, elfogadott TD-D02/05 invariantok és saját future implementation authorization                                                |
| TD05  | **NOT AUTHORIZED** | Külön filesystem I/O és saját disposable fixture authorization, approved reader/capability boundary                                                     |
| TD06  | **NOT AUTHORIZED** | Külön integration authorization; nincs L/R/P/W/S/H/app/ADR módosítás ebből a döntésből                                                                  |

TD-D04 Option A elfogadott: a cap pontosan16777216 bájt, az A1.2 wire-domain
az A1.1 decoded domaintől külön metszet (§6.3.2). Nincs további owner-választás
e capről e feladatban, nincs cap-emelés vagy A1.1-szűkítés. Az architectural
elfogadás nem TD-D02 IMPLEMENTATION AUTHORIZED státusz, nem TD02 engedély,
és nem runtime capacity proof. TD01–TD06 mind NOT AUTHORIZED marad.

Minden későbbi implementációs feladat rögzíti a branch/HEAD/clean baseline-t,
exact file allowlistet, megengedett pure/F vagy D teszteket, actual evidence-t,
history preservationt és owner review-t. A maximum-valid-A1.2-wire-input memória/practical
capacity teszt és az R1 malformed128MiB teszt két külön acceptance követelmény.
Commit/push/PR engedély csak külön explicit felhatalmazásból származhat.

TD-D07 továbbra **DEFERRED**: protected approval store, trusted bootstrap,
deployment signing infrastructure, revocation freshness service, globális
manifest ID ledger és production trust promotion nem indul. Unsupported
assurance fail closed, nincs silent local-mode fallback.

Nem készült PHP-implementáció/manifest/anchor/fixture, nem indult TD01–TD06,
nem történt MySQL/SQL/PoC/VM/identity/ACL-művelet vagy Git staging/commit/push.
**WP7-F01 és WP7-F02 OPEN; transaction PoC NOT READY.**
A dokumentum az elfogadott döntéseket és a külön megmaradó implementációs/runtime
kapukat rögzíti. **TD-D04 DECISION RECORDED — READY FOR OWNER REVIEW**;
a munka itt megáll, explicit implementációs felhatalmazásra vár.
