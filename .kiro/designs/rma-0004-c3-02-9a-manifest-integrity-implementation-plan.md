# RMA C3-02.9-A — Manifest Integrity Implementation Plan

Dátum: 2026-11-17. Hatókör: dokumentáció és statikus forráselemzés.
Verdikt: **PLAN COMPLETE — IMPLEMENTATION APPROVAL REQUIRED**.
Az alábbi PHP/CLI/OS-interfészek és JSON-példák tervek; nem készült implementáció,
manifest, fixture vagy runtime evidence.

## 1. Vezetői összefoglaló

Az A1 subsystem önálló, minimális függőségű PHP CLI-ként fejlesztendő a
PoC-root `integrity/` alkönyvtárában, Laravel/vendor/app bootstrap nélkül.
Schema/pure validation, trusted digest comparison, determinisztikus eredmény
és negatív tesztek Windows 10 Home alatt, ordinary userként tervezhetők;
VM, MySQL, DB-connection és elevation nem előfeltétel. A tényleges platform-
és PHP-képességek ebben a dokumentációs feladatban nem kaptak runtime próbát.

Két assurance-scope különül el: `CONTENT_ONLY` PHP-ellenőrzés és
`WINDOWS_OBSERVED` natív read-only fájlrendszer-inspection. Utóbbihoz a PHP
path API-jain túl standard-user OS adapter szükséges; hiánya nem értelmezhető
Windows security PASS-nak. A pure/mock teszt lehet PASS, de `SIMULATED`
jelöléssel. Egyik eredmény sem execution authorization.

Az accepted **A1/B2/C2 változatlan**: a későbbi transaction-PoC kizárólag
dedikált standard identityvel, eldobható Windows VM-ben, host sharing nélkül
engedélyezhető. Helyi manifest-fejlesztés ezt nem helyettesíti. A történeti
`evidence/results.json` változatlan; jelenlegi L/R guard nem kerül eltávolításra
e standalone fejlesztési scope-ban sem. A camelCase v1 szerződés és a korábbi
C3-02.8 snake_case javaslat eltérését explicit verzió-/mapping határ kezeli,
nem implicit mezőátnevezés.

## 2. Baseline és vizsgált források

### 2.1. Repository állapot

| Tétel                     | Közvetlen eredmény                                                        |
| ------------------------- | ------------------------------------------------------------------------- |
| Branch                    | `feature/returns-repair-management`                                       |
| HEAD                      | `c8c01cae0df454674fd39c9f26a04015fa105c98`; a megadott `c8c01ca` egyezik  |
| Tracked módosítás / index | Nincs; `git diff --stat` és `git diff --cached --stat` üres               |
| Kezdő untracked 1         | `.kiro/designs/rma-0004-c3-02-6-preflight-evidence-and-poc-readiness.md`  |
| Kezdő untracked 2         | `.kiro/designs/rma-0004-c3-02-7-poc-execution-safety-remediation-plan.md` |
| Kezdő untracked 3         | `.kiro/designs/rma-0004-c3-02-8-execution-architecture-specification.md`  |
| Eltérés                   | Nincs váratlan branch/HEAD vagy kapcsolódás nélküli munkafa-változás      |

A három report létezik és közvetlenül olvasott. A feladatban megadott
Windows 10 Home fejlesztői környezetet követelményként használjuk, nem most
mért OS-azonosságként. Meglévő fájl módosítása nem engedélyezett.

Kezdő megőrzési leltár: **937 fájl**, digest
`4df2f091a2437d9b0a7396cf765c13923c17a5c6164d1a090747b81d0983932a`.
Algoritmus: `git ls-files -z --cached --others --exclude-standard` egyedi,
rendezett UTF-8 pathjaihoz raw-byte SHA-256; a `(path,hash)` listára Python
`json.dumps(..., ensure_ascii=False, separators=(',', ':'))`, majd annak
UTF-8 SHA-256 értéke. Ez review-scope megőrzés, ignored/outside-inventory
file-ra nem bizonyíték; külön inventory artifact nem keletkezett.

### 2.2. Source registry és findings

A forráskulcsokhoz 1-alapú sor vagy dokumentumfejezet tartozik.

| Kulcs | Vizsgált forrás                                                                                                | Konkrét jelentőség                                                                                 |
| ----- | -------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
| C026  | [C3-02.6](rma-0004-c3-02-6-preflight-evidence-and-poc-readiness.md), §4–8                                      | Operátori részhalmaz, G05/G09/G11, nyitott WP7 és execution-stop                                   |
| C027  | [C3-02.7](rma-0004-c3-02-7-poc-execution-safety-remediation-plan.md), §3/7–8/10                                | A1 alternatívák, trust-anchor hiány, append-only output és dependencies                            |
| C028  | [C3-02.8](rma-0004-c3-02-8-execution-architecture-specification.md), §4/5/9/10/12–14                           | Accepted A1/B2/C2, snake_case sémajavaslat, canonical paths/limits és authorization különválasztás |
| C024  | [C3-02.4](rma-0004-c3-02-4-launcher-hardening-results.md), §2–3/6–13                                           | L/README hardening, régi hash-ek történeti érvénye és no-reparse korlátok                          |
| C02   | [C3-02](rma-0004-c3-02-transaction-boundary-poc-results.md), §2/6/8–12                                         | Eredeti ACL-stop, 14 BLOCKED, nem DB-runner által mentett evidence                                 |
| C01   | [C3-01](rma-0004-c3-01-transaction-integrity-proof-plan.md), §8–9/12–15                                        | Isolated environment, proof/cleanup és külön authorization határok                                 |
| D     | [PoC README](../../tests/ProofOfConcept/RmaTransactionBoundary/README.md), Files and isolation / Phase B       | Historical target és külön reviewed output-adaptáció kötelező                                      |
| L     | [launch.ps1](../../tests/ProofOfConcept/RmaTransactionBoundary/launch.ps1), 45/59–118/259–270/338–340          | Natív directory identity és path check; H létezési STOP, source-hash comparison nincs              |
| P     | [Prototype.php](../../tests/ProofOfConcept/RmaTransactionBoundary/Prototype.php), 13/28–64/113–167             | Vendor autoload és DB config; verifierből nem importálható                                         |
| R     | [run.php](../../tests/ProofOfConcept/RmaTransactionBoundary/run.php), 9–13/60–107/375–392                      | Autoload után historical guard, DB setup, utólagos source hash és fix unchecked output             |
| W     | [worker.php](../../tests/ProofOfConcept/RmaTransactionBoundary/worker.php), 7–29                               | P/DB runtime worker; verifierből nem importálható                                                  |
| S     | [schema.mysql](../../tests/ProofOfConcept/RmaTransactionBoundary/schema.mysql), 3–77                           | Synthetic SQL source, verifier számára inert byte-input, nem végrehajtható lépés                   |
| H     | [evidence/results.json](../../tests/ProofOfConcept/RmaTransactionBoundary/evidence/results.json), 2–54/267–275 | Agent-recorded 2026-11-05 failure, 0 executed/14 BLOCKED; hat historical source hash               |

**F01:** H nem A1 current-input manifest; nincs current integrity verifier.
L:338–340 csak `Test-Path`, R:11–13 csak `is_file` megállás. **F02:**
R:375–383 `hash_file('sha256', ...)` öt source utólagos leltára, nem trusted
pre-execution validation; P:13 vendor closure nincs benne. **F03:** H hat
bejegyzése L/P/D/R/S/W; L és D régi hash-e a dokumentált C024 hardening
előtti tartalomhoz tartozik (C024 §2–3, C026 §2), P/R/S/W egyezése nem DB-proof.
**F04:** L directory `volume/fileId` megfigyelése nem file-content hash és
nem általános hardlink/ADS/TOCTOU megoldás. **F05:** C028 §4 snake_case mezőnevei
eltérnek e feladat camelCase követelményétől; még nincs actual v1 manifest,
ezért tervezett explicit szerződésfinomítás, nem történeti adat-migráció.

## 3. Scope és non-goals

In scope: standalone parser/schema/canonical inventory/hash/trust selection,
filesystem policy+adapter, structured result/CLI, no-DB disposable unit/IO
fixtures, historical preservation és későbbi VM input-result interface.
Kizárva: VM, account/UAC/ACL provisioning, elevated helper, MySQL/SQL/PDO,
Laravel application/bootstrap/config, migrations, L/R/P/W módosítás vagy
indítás, régi evidence/hash felülírás, PoC-launch és Git write műveletek.

A verifier nem futtathat verified inputot, nem oldhat fel entrypointot
dinamikus `require`/callback formájában, nem importálhat P/R/W-t és nem
dot-source-olhat L-t. S/H csak bytes. CLI csak saját ismert osztályait
töltheti be explicit file-mapből; app `vendor/autoload.php`, `bootstrap/app.php`,
`.env`, Artisan, project Pest/TestCase nem része a subsystemnek.

Local tests ordinary user saját disposable fixture-jein működnek. Nem
szükséges csomagtelepítés vagy Composer-script: a projekt `composer.json`
`setup` scriptje migrationt, `test` scriptje Artisant is indít, ezért ezek
nem e subsystem entrypointjai. OS helper külön későbbi read-only opcionális
komponens; nem megengedett helyette a launcher meghívása.

## 4. Manifest schema és canonicalization

### 4.1. Exact proposed contract

Format identity: `formatId="rma-poc-inputs"`, `schemaVersion="1.0"`.
CamelCase objektum, minden felsorolt mező kötelező; minden objektumszinten
unknown field elutasított, default/coercion nincs. JSON schema dokumentum és
PHP validator ugyanazon fixture-katalógussal ellenőrzendő; külső schema library
nem szükséges. `files` és `historicalArtifacts` két exact inventory, diszjunktan.

| Mező                         | Exact típus / feltétel                                                                                                      |
| ---------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| `formatId`, `schemaVersion`  | A fenti exact stringek; legacy/unknown version fail closed                                                                  |
| `manifestId`, `pocVersion`   | ASCII `[A-Za-z0-9._-]{1,80}`, nem üres; ID reuse más digesttel tiltott                                                      |
| `createdAt`                  | Exact UTC `YYYY-MM-DDTHH:mm:ssZ`, valódi dátum; idő nem hitelesítés                                                         |
| `exampleOnly`                | Boolean; approved verificationhez `false`; `true` csak docs/test fixture                                                    |
| `files`                      | Nem üres rendezett lista `path`, `sizeBytes`, `sha256`, `category` objektumokból                                            |
| `historicalArtifacts`        | Nem üres rendezett lista `path`, `sizeBytes`, `sha256`, `provenanceRef` objektumokból; H kötelező a deployment profilban    |
| `verificationMetadata`       | Exact objektum: `baselineCommit`, `profileId`, `profileSha256`, `provenanceRef`; nincs önbevallásos `verified`/`authorized` |
| `baselineCommit`             | 40 lowercase hex; approved dirty inputs külön file-listája a valós byte-digestet adja, nem HEAD-only proof                  |
| `profileId`, `provenanceRef` | ASCII `[A-Za-z0-9._-]{1,80}`, külső record referencia, nem secret/path/URL                                                  |
| `profileSha256`, `sha256`    | Exact 64 lowercase hex, raw-byte SHA-256, nem newline-normalizált tartalom                                                  |
| `path`                       | §6 ASCII relative grammar; canonical roothoz viszonyított, nem abszolút                                                     |
| `sizeBytes`                  | Nemnegatív integer JSON token, nem float/string/exponent/bool; maximum 2147483648                                           |
| `category`                   | Enum `poc_code`, `schema`, `toolchain`, `dependency`, `configuration`, `documentation`                                      |

Limit v1: strict 64-bit PHP8.4+ CLI ajánlott baseline; manifest max16 MiB,
100000 összes entry, max8 GiB file-byte total, path max240 ASCII karakter,
JSON nesting max16. Ezek C028 §4 tervezett limitek, nem most mért kapacitások.
Unsigned overflow/32-bit PHP/limit hiány elutasítás. A manifest maga az egyetlen
inventory-kivétel: külön digestelt metadata, nem szerepel saját `files` listájában.
Anchor/output/verifier source más approved rootban él; minden más extra input
file, executable vagy nem executable egyaránt `INPUT_UNEXPECTED`.

### 4.2. JSON byte-contract és rendezés

UTF-8 BOM nélkül; manifestben minden kulcs és string value ASCII, ezért a
Unicode normalization nem alakíthat át pathot vagy azonosítót. A parser
érvényes JSON-t és duplicate key-t **decoded key** alapján vizsgál, még az
associative array-vá alakítás előtt: `path` és `\u0070ath` nem külön kulcs.
PHP `json_decode` önmagában nem elegendő duplicate-key guard; bounded strict
tokenizer kell, amely nem javít hibát és nem enged comments/trailing comma-t.
Invalid UTF-8, unpaired surrogate, duplicate vagy malformed JSON STOP.

Canonical v1 JSON: objektumkulcsok recursive ASCII byte-order szerint,
`files`/history listák canonical path ASCII byte-order szerint; null csak
ott engedett, ahol a külön result schema megengedi, manifestben sehol.
Boolean lowercase, integer decimal leading plus/zero/exponent nélkül;
string escaping a JSON kötelező escape-jei szerint, slash nem escaped,
extra insignificant whitespace nincs. Pontosan egy sor + egy záró LF (`0A`),
CRLF/BOM/indent nincs. E canonicalizer szűk szerződés, **nem RFC8785 általános
implementáció**. PHP serializer exact flag/escaping golden-byte tesztekkel rögzítendő.

Trust comparison mindig a **beolvasott raw manifest byte-ok** SHA-256 értékével
történik. A verifier nem recanonicalizál tampered inputot digest-ellenőrzés
előtt. Parse után a reserialized canonical bytesnek exact egyeznie kell a
received bytes-szal; eltérés `MANIFEST_NONCANONICAL`, nem automatikus javítás.
Hashkészítés előtt file-contentet sem normalizálunk. Duplicate path/case
collision a két inventory közös halmazában is tiltott.

### 4.3. Illusztratív JSON-példa

Az alábbi pretty-printed szemléltető objektum **nem canonical actual manifest**;
az ID-k/hash-ek/méretek kitalált példák, `exampleOnly=true`, inventory hiányos.
Production verifiernek el kell utasítania. Canonical byte-formát a majdani
serializer/golden fixture rögzíti; most manifestet nem generálunk.

```json
{
    "formatId": "rma-poc-inputs",
    "schemaVersion": "1.0",
    "manifestId": "example-inputs-v1",
    "pocVersion": "example-0.1",
    "createdAt": "2000-01-01T00:00:00Z",
    "exampleOnly": true,
    "files": [
        {
            "path": "payload/readme.txt",
            "sizeBytes": 0,
            "sha256": "0000000000000000000000000000000000000000000000000000000000000000",
            "category": "documentation"
        }
    ],
    "historicalArtifacts": [
        {
            "path": "history/result.json",
            "sizeBytes": 0,
            "sha256": "0000000000000000000000000000000000000000000000000000000000000000",
            "provenanceRef": "example-history"
        }
    ],
    "verificationMetadata": {
        "baselineCommit": "0000000000000000000000000000000000000000",
        "profileId": "example-content-v1",
        "profileSha256": "0000000000000000000000000000000000000000000000000000000000000000",
        "provenanceRef": "example-review"
    }
}
```

### 4.4. C028 compatibility boundary

C028 §4 korábbi snake_case `schema_version/manifest_id/poc_version/
created_at_utc/inputs/historical_artifacts/...` **javaslat**, nem létező
manifest. E terv a most kifejezetten kért `schemaVersion/manifestId/pocVersion/
createdAt/files` interface-t specifikálja. Mapping: `inputs -> files`,
`historical_artifacts -> historicalArtifacts`, `size_bytes -> sizeBytes`,
`baseline_commit/profile_id/profile_sha256 -> verificationMetadata.*`;
az új `formatId` diszkriminátor és timestamp másodperc-pontosság tudatos finomítás.
Mixed/legacy field shape elutasított; implicit alias/migráció nincs.
Az A1 döntés nem változik, de az exact wire-contract owner/backend elfogadása
szükséges implementáció előtt, és a későbbi C028 VM integration adapter csak
e jóváhagyott formát használhatja. A régi dokumentumot most nem írjuk át.

## 5. Trust model

### 5.1. Mit bizonyítunk?

**Integrity:** a megfigyelt bytes/inventory egyezik az expected értékekkel.
**Authenticity:** az expected manifest digestet az illetékes owner független
csatornája választotta ki. **Provenance:** mely baseline/bundle/review készítette
és hagyta jóvá. **Execution authorization:** külön B2/C2/run-scope gate;
nem része a verifier jogának. Manifestben tárolt saját hash/approvalRef vagy
boolean nem hitelesít önmagát és nem jogosít indításra.

Threat model: az input-bundle írója módosíthat file-t és manifestet, de nem
írhatja a trusted expected digestet, a verifier binary/source-t és az owner
approval recordját. Ez ellen a file+manifest együtt cserélése hash-mismatch.
Ha a támadó ugyanazon user minden file-ját/CLI-jét/memóriáját írhatja, a
helyi PHP-script önmagát és anchorját nem tudja biztonsággal megvédeni. Ezt
nem oldja meg egy második hashfile ugyanabban a repositoryban.

### 5.2. Anchor contract és provisioning

Az `AnchorProvider` trusted caller által kiválasztott, **input bundle-on kívüli**
read-only logikai recordot ad. Exact mezők: `anchorVersion="1.0"`, `anchorId`,
`manifestId`, `manifestSha256`, `profileId`, `profileSha256`, `approvalRef`,
`verifierReleaseId`, `status="ACTIVE"`. ID/hash grammar mint §4; unknown field,
duplicate key, rossz formátum/mismatch/REVOKED vagy hiány STOP. A recordhoz
tartozó protected store/bootstrap és out-of-band approval nem a record saját
mezőjével bizonyított. A subsystem nem tartalmaz auto-create/update parancsot.

Jövőbeli deployment: owner-controlled archive/approval record, VM templateből
pinned trusted bootstrap, kívülről ellenőrzött anchor delivery; standard user
nem írja. Anchor frissítés csak owner/security change-ref, régi+új digest és
verifier-release compatibility review után, új verzió/ID-val. Rollback sem
visszavont anchor újraaktiválása automatikusan, hanem explicit jóváhagyás.
Auditban expected/actual manifest digest, anchor ID/approvalRef és selector
eredete megmarad; secret nincs benne.

Local development: fixture anchor külön, saját disposable `control/` rootban,
bundle `payload/` rootjától diszjunktan, trusted test-harnessből injektálva.
Ez szándékosan `trustMode=FIXTURE`, `authenticity=SIMULATED`; nem production
authenticity. Külön ordinary-user local external-pin ellenőrzésnél az operátor
a repositorytól független approved recordból kézzel választ expected digestet;
ez legfeljebb `OPERATOR_ATTESTED`, nem same-user tamper elleni OS-garancia.
Ehhez nem kérünk új ACL-t/elevationt, és nem állítunk erősebb boundary-t.

CLI nem keres anchor-t a CWD-ben, bundle-ban vagy environment fallbackból.
Deploymentben a trusted bootstrap pineli a selector/store helyét; tetszőleges
CLI `--trusted-digest` önmagában nem trusted bootstrap. Fixture CLI külön
mode-ot és jól látható SIMULATED scope-ot igényel. Bundle által adott anchor
path, anchor/manifest/input/output root átfedés vagy substitution elutasított.
Helyi proof nem léptethető át automatikusan VM-approved provenance-ra.

## 6. Filesystem safety és assurance-határ

### 6.1. Canonical relative path policy

Csak `/` separator és ASCII `[A-Za-z0-9._-]+` szegmens. Üres path/szegmens,
`.`/`..`, leading slash, backslash, drive prefix, UNC/device namespace,
`\\?\`, `\\.\`, colon/ADS syntax, NUL/control char, trailing dot/space tiltott.
Reserved device basename extensiontől függetlenül tiltott: CON/PRN/AUX/NUL,
COM1–9/LPT1–9 és a Windows device aliasok CONIN$/CONOUT$; a `$` amúgy sem
megengedett. ASCII-casefold alatt közös inventory collision STOP; nincs
case-sensitive directory kivétel. Pathot nem javítunk/trimelünk/Unicode-
normalizálunk automatikusan. Non-ASCII NFC/NFD path már a grammar miatt
elutasított: így a normalization collision nem rejtett azonosság.

Input root, manifest, anchor és result root explicit fully qualified local
approved path; remote/UNC/provider path és root-overlap tiltott. Az abszolút
configpath más szabály, mint a manifest relative path, de ugyanúgy no-reparse
és canonical identity policy vonatkozik rá. A roothoz csatolás és resolved
boundary összevetése kötelező; puszta prefix (`root2` vs `root`) nem elegendő.
240-karakter relative limit mellett a teljes absolute path támogatása is
ellenőrzött; unsupported long-path állapot UNKNOWN, nincs 8.3 alias fallback.

### 6.2. PHP-only és OS inspection feladatok

| Tulajdonság                       | Ordinary PHP API / pure validation                                                            | További OS-level proof és fail-closed határ                                                                                                     |
| --------------------------------- | --------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| JSON/path grammar/collision/order | Pure token/schema/path validation teljesen tesztelhető                                        | Nem bizonyít fizikai targetet                                                                                                                   |
| Content size/hash/read errors     | Saját stream raw-byte hash, checked reads/EOF és size; stat cache kezelése, pre/post metadata | Path-reopen/mutation ellen ez korlátozott; nem atomikus whole-tree snapshot                                                                     |
| `realpath`/`lstat`/`is_link`      | Hasznos észlelés és boundary hint                                                             | Windows minden junction/reparse/mount-point fajtájára nem tekinthető teljes proofnak                                                            |
| Reparse/symlink/junction          | Pozitív link-jelzésnél azonnali DENIED                                                        | Native attribute/reparse tag minden komponensen, handle-final-path/root-volume identity kell; query failure UNKNOWN                             |
| ADS                               | Colon a manifest pathban pure módon tiltott                                                   | Meglévő file named streamjeit ordinary PHP stat/hash nem teljes körűen látja; native stream enumeration, minden nem default data stream tiltott |
| Hard links                        | `stat` nlink/inode legfeljebb diagnostic, Windows megbízhatóság nem feltételezett             | Native link count + volume/file ID; `linkCount != 1` vagy két path ugyanazon file ID-re DENIED                                                  |
| Case/Unicode                      | ASCII-only+casefold policy determinisztikus                                                   | OS identity/alias/final path továbbra kell, lexical canonicalization nem fizikai proof                                                          |
| File change/replace               | Stream hash+pre/post size/mtime segít, mock mutation determinisztikusan tesztelhető           | Held handle identity/hash és no-delete/no-write sharing stratégia capability proofja kell; tree enumeration race marad                          |
| Permission denied/unsupported FS  | Read/query failure sosem PASS                                                                 | Nem kérünk elevated retryt, ACL-változtatást vagy idegen könyvtár probe-ot                                                                      |

`FilesystemInspector` két backendje: **PhpFilesystemInspector** csak
`CONTENT_ONLY`; **WindowsFilesystemInspector** későbbi fixed read-only OS
adapterrel `WINDOWS_OBSERVED`. A local developer a teljes pure/content ágat
standard joggal fejlesztheti. `WINDOWS_OBSERVED` kérésekor hiányzó capability
`CAPABILITY_UNAVAILABLE`, INCONCLUSIVE/DENIED; nincs csendes PHP fallback.
Unit fake adapter capability-je `SIMULATED`, nem actual native proof.

### 6.3. Native adapter és TOCTOU szerződés

Tervezett optional `os/windows-inspect.ps1` helper csak saját approved
root/read requesteket fogad, versioned bounded stdin/stdout JSON-protokollal.
Nem dot-source-olja L-t, nem fogad shell commandot, nem ír ACL-t/file-t,
nem indít más processt és nincs elevated mode. PHP `proc_open` esetén fix
approved absolute helper/PowerShell path, argument-array és sanitized env;
binary/source digest pin, explicit timeout/byte-limit és exit handling kell.
Ez külön későbbi implementáció, jelenleg nincs ilyen képesség igazolva.

Native snapshot tartalma: canonical root/file handle-path, volume/file ID,
attributes/reparse tag, link count, named streams, size és raw-byte hash,
pre/post identity/metadata, teljes ordered inventory és adapter capability/
version. A file hash **ugyanazon held handle-on** készül, mint az identity;
snapshot után PHP path-reopen/hash nem örökölheti ezt a strong claimet.
Helperben no-write/no-delete sharing és directory-handle retention tervezendő;
ha standard jog/target FS ezt nem támogatja, UNKNOWN, nem workaround.

Directory child-create/rename, privileged actor, verify/use közötti későbbi
változás és crash ellen nincs mindenre atomi tree proof. Determinisztikus
pre/post enumeration és file-ID/hash vizsgálat minden észlelt driftre
`FILE_CHANGED`; a nem észlelt race maradványkockázat. VM integrációban a
provisioner által lezárt read-only input és a későbbi execution supervisor
újraellenőrzése szükséges (C028 §4.3). Helyi writable fixture-ben nincs
same-user adversary elleni deployment-garancia. E határt resultban és
tesztcoverage-ben kötelező közölni; canonical path önmagában nem TOCTOU-megoldás.

## 7. Verification pipeline, results és exit code

### 7.1. Determinisztikus sorrend

| Stage | Lépés / stop-szabály                                                                                                                                 |
| ----- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| V01   | Explicit config/schemaVersion/mode/capability/limits és local diszjunkt rootok validálása; sem env/CWD fallback, sem launch callback                 |
| V02   | Trusted caller-selected anchor betöltés és strict formátum/status/verifier profile ellenőrzés; hiányra STOP                                          |
| V03   | Manifest location és root boundary/no-reparse requested-scope vizsgálata; bounded single snapshot bytes beolvasása, nem későbbi újraolvasásból parse |
| V04   | Raw manifest SHA-256 és anchor expected digest constant-time comparison; mismatch esetén nem parse-olunk tovább                                      |
| V05   | Strict JSON tokenization/duplicate-key/UTF-8/type és size/depth limit                                                                                |
| V06   | Exact format/schema/unknown fields/metadata/anchor IDs + canonical reserialize equality; legacy H vagy example manifest elutasítás                   |
| V07   | Inventory ordering, duplicate/casefold/Unicode/reserved/path grammar és cross-list overlap; invalid pathon nincs FS traversal                        |
| V08   | Requested backend capability/structure/ADS/reparse/hardlink/root identity; unsupported/query failure UNKNOWN, ismert tiltott object DENIED           |
| V09   | Expected list és observed teljes input tree ordered enumerate, mindkét inventory + manifest kivétel; count/size limit                                |
| V10   | Missing/unexpected és exact category input-lista ellenőrzés; unknown file nem ignore-olható extension alapján                                        |
| V11   | Raw sizes ellenőrzése minden reachable expected file-on, failed-size file-ra nincs feltételezett hash PASS                                           |
| V12   | Raw-byte SHA-256, history pin, pre/post identity/enumeration/mutation checks; requested-scope szerint capability evidence                            |
| V13   | Stable evaluation + separate observation envelope; checked result writer vagy stdout; hash/readback/write errors külön                               |
| V14   | Fail-closed result/exit; verifier véget ér, nincs launcher/PHP transaction/MySQL hívás                                                               |

Config/anchor/manifest/schema/path hard gate hibánál következő stage nem fut.
Inventory/size/hash stage ismert file-eltéréseit bounded módon gyűjti, sorted
`stage,path,code` sorrendben; unavailable IO nem változik mismatch-ké.
Hiba-class prioritás: usage/config -> untrusted/malformed -> security/integrity
reject -> incomplete capability/IO; ha sértés és unknown együtt van, DENIED
megmarad és `completeness=PARTIAL`. Output-write hiba külön exitet kap, az
eredeti evaluationt megőrzi, amennyiben biztonságos stdouton lehetséges.

### 7.2. Result contract és determinisztikusság

`evaluation`: schema/mode/requested+actual assurance/trustMode/manifest ID és
expected/actual digest, anchor ID/approvalRef, inventory count/checked bytes,
per-path category/expected+actual size/hash, capabilities, historyStatus,
sorted error code-ok, completeness és `inputVerdict=VERIFIED|DENIED|INCONCLUSIVE`.
`executionAuthorization="NOT_EVALUATED"`, `canLaunchPoC=false` **minden** kimenetben.
VERIFIED csak a megjelölt scope összes required assertionjére igaz; content
scope success nem Windows isolation PASS. `authenticity` enum:
`SIMULATED|OPERATOR_ATTESTED|EXTERNAL_ANCHOR`, az utóbbihoz trusted delivery
boundary proof ref kell, nem puszta anchor-file létezés.

`observation`: verification ID, UTC started/finished, monotonic duration,
PHP/OS/adapter version, protected raw diagnostic ref, writer outcome. Ezek
változhatnak futásonként. `evaluationSha256` csak a canonical stable
evaluation byte-okra készül, timestamps/absolute paths/localized OS-message
nélkül. Azonos snapshot/config/anchor mellett evaluation bytes és digest
azonos; az egész timestampes record byte-azonosságát nem ígérjük.
Nondeterminisztikus stream/process errno a protected observationben, stabil
`IO_READ_FAILED` jellegű reason az evaluationben szerepel.

Illusztratív, nem actual result:

```json
{
    "evaluation": {
        "schemaVersion": "1.0",
        "mode": "FIXTURE",
        "assurance": "CONTENT_ONLY",
        "authenticity": "SIMULATED",
        "inputVerdict": "VERIFIED",
        "executionAuthorization": "NOT_EVALUATED",
        "canLaunchPoC": false,
        "errors": []
    },
    "observation": {
        "exampleOnly": true,
        "verificationId": "example-verification"
    }
}
```

Ez részleges result-shape példa, nem teljes output-schema instance vagy
deployment evidence; a valódi result minden fenti mezőt explicit tartalmaz.

### 7.3. Exit és error taxonomy

| Exit | Jelentés                                                   | Példa reason code / továbblépés                                                                                                                   |
| ---- | ---------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| 0    | Requested declared scope VERIFIED és result writer sikeres | Nem execution authorization; fixture/content címke kötelező                                                                                       |
| 10   | Ismert integrity/security DENIED                           | HASH_MISMATCH, SIZE_MISMATCH, INPUT_MISSING/UNEXPECTED, PATH_INVALID, REPARSE_FOUND, ADS_FOUND, HARDLINK_AMBIGUOUS, FILE_CHANGED, HISTORY_CHANGED |
| 20   | Invalid/untrusted input/anchor/schema DENIED               | ANCHOR_MISSING/INVALID, MANIFEST_UNTRUSTED/NONCANONICAL, JSON_INVALID/DUPLICATE_KEY, SCHEMA_UNSUPPORTED, FIELD_UNKNOWN, LEGACY_HISTORY_FORMAT     |
| 30   | Required proof nem állítható elő, INCONCLUSIVE             | CAPABILITY_UNAVAILABLE, IO_READ_FAILED, ADAPTER_TIMEOUT/INVALID_RESULT, IDENTITY_UNVERIFIED; nincs elevated retry                                 |
| 40   | Result/evidence output failure                             | OUTPUT_EXISTS/OVERLAP/UNSAFE, EVIDENCE_WRITE_FAILED/READBACK_MISMATCH; original error result nem törlődik                                         |
| 64   | CLI/config usage error                                     | CONFIG_INVALID, ROOT_OVERLAP, invalid switches; semmi fixture/input mutáció                                                                       |

Native helper raw exitje külön observation adat, nem azonos a verifier
exitjével. Exit0 nem aggregálható PoC-ready állapottá. Megszakítás esetén
hiányos envelope/OS-exit külön jelölt, nem fabricated structured PASS.

## 8. Proposed implementation structure

Minden alábbi útvonal **tervezett új file**, a
`tests/ProofOfConcept/RmaTransactionBoundary/` gyökérhez képest. Most egyik sem
jött létre. Nincs új production dependency, Composer/autoload/config változás.

| Proposed path                                         | Feladat / tiszta interfész                                                                              |
| ----------------------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| `integrity/schema/input-manifest-v1.schema.json`      | §4 machine-readable contract; additionalProperties=false, strict integer/string/enum                    |
| `integrity/src/CanonicalJson.php`                     | Bounded strict tokenizer/duplicate-key guard és szűk canonical serializer, golden bytes                 |
| `integrity/src/Manifest.php`                          | Immutable validated DTO, raw digest és ordered file/history list                                        |
| `integrity/src/ManifestValidator.php`                 | Pure schema/path/order/collision/limits, nincs I/O vagy app require                                     |
| `integrity/src/TrustAnchor.php`                       | Validated anchor DTO + injected read provider; nincs generator/updater                                  |
| `integrity/src/FilesystemInspector.php`               | Interface: capabilities(), snapshot(root, expected paths, limits), result DTO; nincs execute            |
| `integrity/src/PhpFilesystemInspector.php`            | Read-only content/stream backend, explicit CONTENT_ONLY                                                 |
| `integrity/src/WindowsFilesystemInspector.php`        | Fixed native read-helper protocol adapter, explicit Windows capability validation                       |
| `integrity/os/windows-inspect.ps1`                    | Optional native read-only helper §6.3; standalone, nem L import, nincs elevation/write                  |
| `integrity/src/Verifier.php`                          | V01–V14 orchestration és stable evaluation; only injected providers                                     |
| `integrity/src/VerificationResult.php`                | Result model, canonical stable digest, exit mapping és checked create-new writer                        |
| `integrity/verify.php`                                | Standalone CLI explicit local file-map, saját config/anchor/root/scope input; nincs dynamic require     |
| `integrity/schema/verification-result-v1.schema.json` | Evaluation/observation és fail-closed output contract                                                   |
| `integrity/tests/run-tests.php`                       | Kicsi standalone assertion runner, `portable` és `windows` suite, nincs project Pest bootstrap          |
| `integrity/tests/ManifestValidationTest.php`          | Pure schema/parser/canonicalization/path/collision cases                                                |
| `integrity/tests/TrustVerificationTest.php`           | Anchor/substitution/history/deployment-mode downgrade tests                                             |
| `integrity/tests/FilesystemSafetyTest.php`            | Injected adapter + disposable actual content/native capability cases                                    |
| `integrity/tests/CliResultTest.php`                   | CLI exits, determinism, writer failures, no-PoC dependency contract                                     |
| `integrity/tests/FixtureFactory.php`                  | Saját disposable payload/control/output roots, cleanup registry; history csak másolat/synthetic fixture |
| `integrity/tests/fixtures/README.md`                  | Fixture recipe és synthetic immutable sample-leírás; actual H nem írható                                |
| `integrity/INTEGRATION.md`                            | Accepted C028 mapping/result/VM authorization interface és historical preservation                      |

Trusted digest deployment-config **nem** a manifest/subsystem mutable bundle-
könyvtárában él. Local test config a disposable `control/` alatt; jövőbeli
deployment store path/protection az owner/bootstrap feladata. CLI nem generál
actual input manifestet. Evidence/output structure explicit, bundle/control
rooton kívüli create-new `<verification-id>/evaluation.json`, `observation.json`,
`receipt.json`; a két utóbbi receipt hash/readback-részlete és hibaállapota
nem állíthat teljes crash-durabilityt.

Existing files: L/R/P/W/S/H és korábbi reportok **változatlanok** a standalone
A1.1–A1.6 scope-ban. D README frissítése is csak későbbi explicit dokumentációs
integration scope-ban; e tervhez nem szükséges módosítani. A C028 szerint
később szükséges L/R guard/pre-autoload/output adaptáció külön reviewed
integration package, nem local A1-fejlesztés része.

## 9. Automated test matrix — kizárólag disposable fixtures

Jelölések: **P** pure/injected, Windows feature nélkül; **D** ordinary-user
local disposable regular files; **W** native read-only adapter actual NTFS
capability; **F** exact approved PHP CLI subprocess, csak verifier, nem PoC.
**K**: FixtureFactory own registry szerinti file/stream/handle close és csak
pontos saját root cleanup. Link/junction esetén csak saját link object törölhető,
target nem; ha identity/törlési módszer nem bizonyítható, retained fixture
report és STOP, nem általános recursive delete. Anchor/output separate roots.

| ID   | Setup                                                                      | Expected verdict                                  | Required assertion                                                        | Platform dependency                                                                        | Cleanup                                    |
| ---- | -------------------------------------------------------------------------- | ------------------------------------------------- | ------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ | ------------------------------------------ |
| MI01 | Canonical synthetic valid manifest, known bytes és külön fixture anchor    | VERIFIED/0, SIMULATED scope                       | Exact counts/hash/evaluationSha256; canLaunchPoC=false                    | P+D                                                                                        | K                                          |
| MI02 | File byte módosul, méret azonos                                            | DENIED/10                                         | HASH_MISMATCH pontos path, nem parse- vagy DB-hiba                        | D                                                                                          | K                                          |
| MI03 | Listed file hiányzik                                                       | DENIED/10                                         | INPUT_MISSING, nincs silent skip                                          | D                                                                                          | K                                          |
| MI04 | Nem listázott file, köztük extra `.exe` és `.txt`                          | DENIED/10                                         | INPUT_UNEXPECTED mindkettőre, nincs extension ignore                      | D                                                                                          | K                                          |
| MI05 | File size eltér approved listától                                          | DENIED/10                                         | SIZE_MISMATCH expected/actual, nincs hash PASS a hibás file-ra            | P+D                                                                                        | K                                          |
| MI06 | Schema-valid de hibás file SHA az anchorhoz illeszkedő test-manifestben    | DENIED/10                                         | HASH_MISMATCH, jól elkülönül manifest digest hibájától                    | P+D fixture anchor                                                                         | K                                          |
| MI07 | Manifest byte-tamper fixed external pin mellett                            | DENIED/20                                         | MANIFEST_UNTRUSTED, parse stage nem fut                                   | P+D                                                                                        | K                                          |
| MI08 | File+manifest együtt cserélve, eredeti anchor változatlan                  | DENIED/20                                         | Új file-hash sem kerülheti meg external digest mismatch-et                | P+D                                                                                        | K                                          |
| MI09 | Wrong trusted digest, manifest unchanged                                   | DENIED/20                                         | Expected/actual digest és anchor ID elkülönül                             | P                                                                                          | K                                          |
| MI10 | Unsupported schemaVersion, egyébként matching fixture pin                  | DENIED/20                                         | SCHEMA_UNSUPPORTED; nem legacy alias fallback                             | P                                                                                          | K                                          |
| MI11 | Exact duplicate path és cross-files/history overlap                        | DENIED/10                                         | DUPLICATE_PATH, nincs hash traversal                                      | P                                                                                          | K                                          |
| MI12 | `A.txt`/`a.txt` két entry                                                  | DENIED/10                                         | CASE_COLLISION independent of actual case sensitivity                     | P                                                                                          | K                                          |
| MI13 | `a/../x`, `./x`, üres szegmens                                             | DENIED/10                                         | PATH_INVALID és FS adapter call count0                                    | P                                                                                          | K                                          |
| MI14 | Drive-letter/leading slash/device namespace path                           | DENIED/10                                         | PATH_INVALID, nincs absolute read                                         | P                                                                                          | K                                          |
| MI15 | UNC/network path                                                           | DENIED/10                                         | PATH_INVALID, network/file probe count0                                   | P                                                                                          | K                                          |
| MI16 | `file:stream`, colon/ADS syntax                                            | DENIED/10                                         | PATH_INVALID még I/O előtt                                                | P                                                                                          | K                                          |
| MI17 | Native snapshotban actual named stream                                     | DENIED/10                                         | ADS_FOUND; PHP-only nem állíthat ADS-absence-t                            | P; actual W külön                                                                          | K, stream a saját fixture file-lal         |
| MI18 | Injected reparse/junction, majd külön actual saját approved fixture        | DENIED/10 vagy capability hiányra INCONCLUSIVE/30 | REPARSE_FOUND; target traversal count0                                    | P mandatory; actual W capability-gated                                                     | K, csak saját link object                  |
| MI19 | Injected symlink és optional actual symlink fixture                        | DENIED/10, unsupported actual30                   | Nem claimeljük injected proofot actual Windows proofnak                   | P; actual creation privilege/dev-mode igény esetén BLOCKED, nincs setting/elevation change | K; target untouched                        |
| MI20 | LinkCount2 vagy két path azonos volume/file ID                             | DENIED/10                                         | HARDLINK_AMBIGUOUS; PHP stat-only nem strong claim                        | P; actual W saját hardlink capability szerint                                              | K, csak saját bejegyzések                  |
| MI21 | NFC/NFD-equivalent non-ASCII pathok                                        | DENIED/10                                         | Mindkettő grammarból tiltott, nincs normalize-and-accept                  | P                                                                                          | K                                          |
| MI22 | Malformed/truncated JSON és invalid UTF-8/BOM                              | DENIED/20                                         | JSON_INVALID/ENCODING_INVALID, schema/FS nem fut                          | P; matching fixture raw pin                                                                | K                                          |
| MI23 | Duplicate decoded JSON key (`path` és escaped megfelelője)                 | DENIED/20                                         | JSON_DUPLICATE_KEY, nincs last-key-wins                                   | P                                                                                          | K                                          |
| MI24 | Missing anchor                                                             | DENIED/20                                         | ANCHOR_MISSING, manifest/content check nem folytat                        | P+D                                                                                        | K                                          |
| MI25 | Invalid/unknown/mixed-field anchor formátum                                | DENIED/20                                         | ANCHOR_INVALID, coercion/fallback nincs                                   | P                                                                                          | K                                          |
| MI26 | Injected file read/query permission-denied                                 | INCONCLUSIVE/30                                   | IO_READ_FAILED, nem INPUT_MISSING vagy PASS; nincs elevated retry         | P; actual csak külön előre approved saját fixture, nincs ACL-change                        | K                                          |
| MI27 | Determinisztikus mutation hook hash/identity két lépése között             | DENIED/10 ha drift ismert, unknown proof30        | FILE_CHANGED; régi hashre nincs PASS                                      | P+D; actual W held-handle probe külön                                                      | Handles close + K                          |
| MI28 | Output writer short-write/disk-full/readback mismatch injection            | Output failure40                                  | Original evaluation megmarad, EVIDENCE_WRITE_FAILED; nincs success exit   | P; D/F safe writer fixture                                                                 | K; partial output quarantined              |
| MI29 | Input inventory/keys/pretty bytes unordered, CRLF/no final LF              | DENIED/20                                         | MANIFEST_NONCANONICAL vagy sorted-list invalid, nincs auto rewrite        | P                                                                                          | K                                          |
| MI30 | Unknown field, integer string/float/exponent, limit overflow               | DENIED/20                                         | FIELD_UNKNOWN/TYPE_INVALID/LIMIT_EXCEEDED, bounded allocation             | P                                                                                          | K                                          |
| MI31 | Reserved Windows basename/extension/trailing dot, mixed separator          | DENIED/10                                         | PATH_INVALID, no OS normalization fallback                                | P                                                                                          | K                                          |
| MI32 | WINDOWS_OBSERVED request adapter nélkül/invalid helper reply/timeout       | INCONCLUSIVE/30                                   | CAPABILITY_UNAVAILABLE/ADAPTER_INVALID_RESULT/TIMEOUT, nincs PHP fallback | P; actual W külön                                                                          | K; csak helper own-process timeout cleanup |
| MI33 | Anchor/output/bundle overlap; existing output vagy reparse output          | Usage64 vagy unsafe-output40                      | Overlapot create előtt tiltja, meglévő evidence unchanged                 | P+D/W capability szerint                                                                   | K, existing fixture hash unchanged         |
| MI34 | H-shape current schema helyett, vagy history fixture byte-mutation         | DENIED/20 legacy /10 HISTORY_CHANGED              | Nincs régi hash update vagy source/current keverés                        | P+D, actual H read-only comparison                                                         | Csak másolat K; actual H unchanged         |
| MI35 | Azonos snapshot/config/anchor két evaluation, más envelope timestamp       | VERIFIED/0 declared scope                         | Evaluation bytes/digest exact azonos, envelope lehet eltérő               | P+D                                                                                        | K                                          |
| MI36 | CLI malformed args, fixture mode/assurance mismatch és stdout writer error | Usage64 / capability30 / output40                 | Exact exit taxonomy, no launcher/import/callback; canLaunchPoC=false      | P+F                                                                                        | K                                          |

Pure testeknél a fixture anchor az adott hibatípushoz matching lehet, hogy
a parser/schema/path stage-ig eljussunk; ez nem production anchor-frissítési
engedély. Native negates injected+actual eredménye két külön tesztrekord.
Junction/symlink creation vagy actual denied ACL előállítás, ha az ordinary-user
környezet nem tudja, **nem** indok UAC/Developer Mode/ACL módosítására: actual
case BLOCKED, capability hiány dokumentált. Preprovisioned fixture import is
külön operational authorization, nem a local test implicit joga.

`portable` suite P/D/F eseteket futtat DB/VM nélkül; passing result csak e
scope-ra érvényes. `windows` suite W mandatory capability proofjait is kéri;
hiány esetén suite INCONCLUSIVE/30, nem skip-with-green teljes Windows
assurance. Mindkettőnél actual coverage list és retained fixture állapot
kötelező. A verifier/CLI dependency review-ja tiltja PoC/app imports és
launch függőségek jelenlétét; ez statikus scope-gate, nem általános malware proof.

## 10. Historical compatibility és migrációs korlát

H egyben az eredeti historical manifestként hivatkozott **evidence JSON**;
nem két külön megőrzendő file. Exact eredeti út:
`tests/ProofOfConcept/RmaTransactionBoundary/evidence/results.json`.
Expected raw-byte hash:
`a2e52bc4f68efea95035caa8ec8ddcac9bf941ee13bf30f516f39312449bc9b5`.
Régi branch/HEAD/date/FAIL/BLOCKED/provenance/source_sha256 változatlan.
L/D current drift C024 szerint nem írható vissza a historyba, és H egyezése
nem igazolja a mai inputot. Source-file identity és history byte-preservation
két külön assertion (C027 §3.1, C028 §2/4.5).

Új `formatId/schemaVersion/files` shape-only validator H `artifact_kind/tests/
source_sha256` formátumát `LEGACY_HISTORY_FORMAT`-ként elutasítja current
manifest szerepben. A history listingben H inert file, a hozzá tartozó approved
pin kívülről is egyeztetett. History input byte-mismatch DENIED, nincs
regenerate/repair command. A standalone subsystem nem hoz létre valódi
`inputs-v1.json`-t automatikusan; azt csak későbbi külön approved bundle
freeze lépés készítheti e wire-contract szerint.

Future migration sorrend: pin H -> implement/test A1 independently -> approve
current input inventory és anchor -> VM bootstrap integration -> L/R replacement
gates és új run-output együtt reviewed -> separate execution authorization.
Local A1.1–A1.6 nem távolítja el L/R STOP-ot, nem rewrite-olja H-t és nem
teszi T01–T14-et futtathatóvá. Rollback az új verifier/profil deaktiválása,
utolsó approved verzió, régi/új evidence megőrzése; history restore csak
független byte-exact archived originalból owner-controlled incident recovery.

## 11. Future C2 VM integration contract

| Boundary              | A1 bemenet/kimenet és külön gate                                                                                                                            |
| --------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Artifact import       | Frozen bundle/media ID, independent approved manifest digest, exact format profile; host->guest offline read-only transfer C028 §6 szerint                  |
| Trusted delivery      | Guest által nem írható template bootstrap/anchor store; pin verifier release; local FIXTURE/SIMULATED result nem importálható approved deployment proofként |
| Manifest verification | `WINDOWS_OBSERVED`, history/current inventory, canonical/size/hash/native capability record; VM read-only input closure külön required                      |
| Standard identity     | B2 supervisor actual SID/token/groups/integrity és child gates; A1 nem hoz usert létre és nem állít tokent                                                  |
| Authorization         | Separate owner run-scope/profile/image/expiry gate consumes A1 evaluation+digest és freshness; `inputVerdict=VERIFIED` önmagában nem approval               |
| Use boundary          | Bootstrap pre-autoload check, read-only inputs, bound file identities, későbbi supervisor recheck; verifier nem indít L/R/P/W/MySQL-t                       |
| Evidence              | A1 evaluation és envelope digest a run indexbe kötött; actual verifier/anchor/provenance/assurance scope megőrzendő                                         |
| Export                | C028 §9 offline output-disk shutdown/detach/quarantine/readback/host receipt; A1 local result writer nem host-export szolgáltatás                           |
| Cleanup               | Verifier csak saját read handles/helper és saját result/temp fixture erőforrásait zárja; DB/VM teardown a supervisor/platform owneré                        |

Az authorization consumer schema csak approved A1 wire-versiont fogadhat,
manifest/profile/anchor/image/run binding és input read-only proof nélkül STOP.
Expired/stale evaluation vagy run közbeni drift új verificationt igényel,
nem csendes result reuse. A1 nem helyettesíti B2/C2/MySQL instance identity/
transaction oracle ellenőrzést. WP7-F01 OPEN — BLOCKER és WP7-F02 OPEN —
PARTIALLY VERIFIED — HIGH változatlan, runtime proof nem keletkezett.

## 12. Independently reviewable implementation slices

Minden útvonal a §8 proposed allowlistre utal, az `integrity/` alatt. Minden
slice-nál tiltott existing L/R/P/W/S/H/app/migration/ADR/config módosítás,
DB/VM/account/ACL/elevation/PoC-run és Git write. Unit fixture létrehozás is
csak a későbbi implementációs feladat engedélyével; most nincs futtatás.

| Slice                       | Files to create / modify                                                                                                                      | Dependencies                                                           | Acceptance / security                                                                                         | Tests                                                                     | Rollback                                                                     |
| --------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------- | ---------------------------------------------------------------------------- |
| A1.1 Schema/pure validation | `schema/input-manifest-v1.schema.json`, CanonicalJson/Manifest/ManifestValidator, ManifestValidationTest                                      | Owner/backend exact camelCase/version/canonical byte-contract approval | Strict parser, no duplicate/coercion, sorted ASCII paths, bounds; nincs I/O/app import                        | MI10–16/21–23/29–31 pure golden+negative                                  | Új contract/verifier entry letiltása; régi forrás változatlan                |
| A1.2 Trust/digest           | TrustAnchor, Verifier trust stages, TrustVerificationTest                                                                                     | A1.1, selected fixture/external anchor interface                       | File+manifest substitution fixed independent pin mellett tiltott; local authenticity scope látható            | MI07–09/24–25/34; combined substitution negative                          | Anchor version deaktiválás; auto-update nincs, audit/history megmarad        |
| A1.3 Filesystem inspection  | FilesystemInspector/PhpFilesystemInspector/WindowsFilesystemInspector, optional `os/windows-inspect.ps1`, FilesystemSafetyTest/FixtureFactory | A1.1–2 interface és standard-user capability review                    | Content vs native scope, no-follow/full inventory, drift UNKNOWN/DENIED, helper read-only/no-elevation        | MI01–06/17–20/26–27/32–33; actual W külön capability evidence             | Native backend letiltás, strong request30; nincs silent downgrade/ACL-change |
| A1.4 CLI/results            | `verify.php`, VerificationResult, result schema, CliResultTest                                                                                | A1.1–3 stable result contract; PHP-only backend elsőként használható   | Exact exits, deterministic evaluation, checked create-new output, no PoC import/process                       | MI28/33/35–36 + no-DB CLI scope assertions                                | CLI deactivate; already created evidence nem felülírható                     |
| A1.5 Negative automation    | `tests/run-tests.php`, összes §8 Test és fixture recipe                                                                                       | A1.1–4; synthetic roots és cleanup registry                            | Portable suite no DB/VM/elevation, windows coverage nem hamis green, safe retained fixture report             | Teljes MI01–36 applicability/actual-vs-mock matrix                        | Csak exact saját fixture cleanup; ambiguous link/path retained owner review  |
| A1.6 Historical/VM contract | `INTEGRATION.md`, TrustVerificationTest history/result-consumer fixtures                                                                      | A1.1–5 és C028 mapping elfogadott                                      | H hash unchanged, legacy H current szerepben reject, future integration entrypoints dokumentáltan külön scope | MI34 + external consumer reject SIMULATED/content/stale/mismatch fixtures | New integration profile disabled, history és reports immutable               |

A slice feladata akkor review-ready, ha full scoped diff, actual futott tesztlista,
platform/capability evidence, skipped/blocker indok és history preservation
megvan. A native backend hiánya mellett portable core fejleszthető/review-zható,
de a teljes Windows assurance slice nem jelölhető bizonyítottnak. Bármely
PowerShell/helper dependency bővítés külön review és pin; nincs automatikus
Composer installation, Laravel plugin vagy production bootstrap.

## 13. Risk register

| ID    | Risk                                                     | Severity | Mitigation / source                                                                           | Residual risk                                                                |
| ----- | -------------------------------------------------------- | -------- | --------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------- |
| A-R01 | Manifest és file együtt cserélhető                       | HIGH     | Independent anchor, pinned verifier, MI08; C027 §3/C028 §4.3                                  | Anchor/verifier kompromittálás esetén helyi hash nem authenticity            |
| A-R02 | Trust-anchor compromise/update abuse                     | HIGH     | Owner change-ref, versioned ACTIVE record, no generator/auto-update, external provisioning    | Same-user attacker a local fixture controlt is átírhatja; scope SIMULATED    |
| A-R03 | Path canonicalization/device/ADS escape                  | HIGH     | Strict ASCII grammar+native inspection, MI13–21/31; L:59–118 részmechanizmus nem teljes proof | OS alias/provider/long-path capability eltérés, unsupportedra STOP           |
| A-R04 | TOCTOU/file replacement                                  | HIGH     | Held-handle hash/identity, pre/post enumeration, VM readonly closure; C028 §4.3               | Nem atomi whole-tree snapshot, privileged actor vagy verify/use utáni drift  |
| A-R05 | Windows reparse/hardlink/ADS eltérés PHP-tól             | HIGH     | Explicit backend capability, no false PHP strong PASS, MI17–20/32                             | Native helper correctness/read access platformfüggő, actual proof külön kell |
| A-R06 | False-positive verification / duplicate JSON keys        | HIGH     | Strict tokenizer, schema/canonical equality, exact result scope, MI23/29–30                   | Manual parser/schema drift; golden és negative tests szükséges               |
| A-R07 | Evidence corruption/unchecked write                      | MEDIUM   | Checked create-new/readback, original evaluation preservation, MI28; R:383 ellenpélda         | OS crash/last record loss és same-user output tampering                      |
| A-R08 | Historical evidence modification                         | HIGH     | Pinned H byte-hash, no generator/repair, legacy rejection, MI34                               | Külső archive/trust-store hiba külön incident recovery                       |
| A-R09 | Accidental PoC/application execution                     | BLOCKER  | Standalone explicit autoload map, no P/R/W/L import, no execute callback, test no-DB contract | Kompromittált verifier binary kívül a korlátozott threat modellen            |
| A-R10 | CamelCase/snake_case contract drift                      | MEDIUM   | `formatId` + exact schema + explicit C028 mapping, mixed shape reject; F05                    | Consumer/verifier verziók eltérhetnek, integration approval gate kell        |
| A-R11 | Portable/mock eredményből VM authorization következtetés | HIGH     | canLaunchPoC=false, NOT_EVALUATED és SIMULATED/CONTENT_ONLY címkék, consumer negative         | Hibás külső consumer/reviewer a mezőket figyelmen kívül hagyhatja            |
| A-R12 | Fixture cleanup rossz targetet érint                     | HIGH     | Exact owner registry/no-follow/link-only removal/retained ambiguity                           | Standard-user FS collision és concurrent mutation; nincs broad delete        |

Severity a megnevezett biztonsági határra vonatkozik; a local pure core
fejlesztését nem tiltja minden native proof hiánya. A09 a no-PoC boundary
sértése esetén blocker, nem bizonyított jelenlegi incident.

## 14. Open decisions és owner approval

| Döntés                           | Opció / ajánlás                                                                                                | Következmény / külön jóváhagyás                                                                                                      |
| -------------------------------- | -------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| OD-A01 Wire-contract             | CamelCase `formatId=rma-poc-inputs`, v1.0 e terv szerint; snake_case automatikus alias helyett explicit reject | Owner/backend approval szükséges A1.1 előtt; A1/B2/C2 nem változik, C028 consumerhez dokumentált adapter                             |
| OD-A02 Local trust scope         | FIXTURE/SIMULATED default fejlesztés; operator external-pin csak külön evidence scope                          | Local same-user authenticityt nem állítunk; deployment external/protected store külön security/platform approval                     |
| OD-A03 Native read-only backend  | Optional fixed PowerShell/native read helper standard userként; PHP-only portable core külön                   | Exact Win10 Home/NTFS/PHP/PS/API capability, helper pin és no-write proof kell; unsupported BLOCKED, nincs elevation vagy ACL-change |
| OD-A04 Test feature availability | Portable mandatory + Windows actual capability suite külön                                                     | Real symlink/reparse/denied fixture ha nem ordinary-user előállítható, explicit blocked actual coverage; mock nem helyettesíti       |
| OD-A05 Parser/runtime limits     | §4 bounded strict PHP8.4+/64-bit canonical contract, no dependency                                             | Build/path/serializer golden tests és exact local version rögzítése majdani feladatban; most nem mért version                        |
| OD-A06 Output/retention          | Explicit diszjunkt create-new output és local raw protection, VM archive policy később                         | Store/custodian/retention/permission provenance kijelölendő, nem implicit existing evidence target                                   |
| OD-A07 Scope authorization       | A1.1–A1.6 standalone implementáció + ordinary-user disposable tests külön engedélyezhető                       | Nem L/R guard removal, manifest regeneration, VM/account/ACL/MySQL/transaction vagy commit approval                                  |

A megvalósítás előtt nem tisztázott capabilityre fail-closed út van, nem
improvizált workaround. Nem találtunk olyan bizonyított inkompatibilitást,
amely az accepted A1/B2/C2 újranyitását indokolná. A wire-contract és native
adapter részletdöntése nyitott és megnevezett, runtime proof nincs helyettesítve.

## 15. Final readiness verdict és dokumentációs átadás

**PLAN COMPLETE — IMPLEMENTATION APPROVAL REQUIRED.** A standalone schema,
trust/filesystem/pipeline/result/interface, MI01–MI36 negative test matrix,
hat implementation slice, historical preservation és future VM gate szerződés
implementációs review-ra részletezett. Helyi fejlesztés/tesztelés később
VM/MySQL/elevation/Laravel nélkül végezhető a megjelölt scope-ban; a native
actual proof és protected deployment provenance külön capability/gate.

Releváns DoD-típus dokumentáció, security/tesztinfra csak statikus elemzés.
Validáció: lokális Prettier-check, 15 főfejezet, relatív source-linkek,
illusztratív JSON-ok parse-ja és required azonosítók, 36 teszt/hat slice,
UTF-8/fence/whitespace/Git scope és kezdeti tracked+untracked fájlok raw-byte
digest-megőrzése. Ez nem runtime/security/Windows assurance teszt.
PHP/app/Pint/PHPStan/DB/frontend/build/full gate nem futott: nincs executable
változás, e feladat documentation-only. Schema/manifest generator/unit/native
fixture nem futott. Mermaid/VM/SQL/PDO proof nem állított eredmény.

README/AGENTS/steering/ADR/domain/playbook/workflow/memory/backlog/i18n és
korábbi report frissítés N/A: pontosan egy új terv engedélyezett, alkalmazás-
vagy accepted architekturális döntés nem módosult. Csak a kért új Markdown
jött létre, a három pre-existing untracked report és minden meglévő PoC/history
file változatlan. A tényleges dokumentációs checkeredmény az átadásban szerepel.

Nem történt PHP/PowerShell implementáció, launcher/harness/manifest/history
módosítás vagy regenerálás, VM/account/ACL/UAC/token konfigurálás, MySQL/DB-
kapcsolat/SQL/transaction-PoC futtatás, staging/commit/push/merge/rebase/
branchváltás. A munka itt megáll; **projektgazdai review és külön
implementációs jóváhagyás szükséges**.
