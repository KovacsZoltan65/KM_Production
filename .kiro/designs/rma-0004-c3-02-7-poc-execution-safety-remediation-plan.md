# RMA C3-02.7 — PoC Execution Safety Remediation Plan

Dátum: 2026-11-16. Hatókör: dokumentáció és statikus forráselemzés.
Státusz: **PLAN COMPLETE — IMPLEMENTATION APPROVAL REQUIRED**.
Minden alábbi változtatás és futási eljárás javaslat, nem elvégzett művelet.

## 1. Vezetői összefoglaló

A C3-02.6 G09 blokkolóját nem a történeti manifest törlésével kell feloldani.
Az ajánlott megoldás **A1: külön, verziózott executable-input manifest és
run-specifikus evidence**, a régi `evidence/results.json` változatlan megőrzésével.
A jelenlegi fájl történeti eredmény és forráshash-leltár; a launcher nem
ellenőrzi a hash-eit, hanem a fájl létezésénél megáll. A hash-drift egy része
a dokumentált C3-02.4 hardening következménye, nem bizonyított jogosulatlan módosítás.

Jogosultsági ajánlás: **B2: dedikált standard tesztidentitás**, az elsődleges
**C2: eldobható Windows VM-ben**, host WAMP elérés, hostmegosztás és production
credential nélkül. Ez tervezett profil, rendelkezésre álló VM/platform/account
nincs igazolva. B1 és C1 kisebb adaptációjú alternatíva, de ugyanazon operátori
SID önmagában nem zárja ki a WAMP-fájlok elérését. B3 reduced-token megoldás
első változatként nem ajánlott a token-, ACL- és cleanup-bizonyítás összetettsége miatt.

T01–T14 forrása létezik, **változatlanul nem futtatható**: launcher/runner
manifest-stop, token- és instance-identity kapuk, artifact/cleanup szerződés,
valamint célzott test-oracle javítás szükséges. Ezek előkészítése külön
implementációs feladat; a környezet és a transaction proof végrehajtása további
explicit engedély. **WP7-F01 OPEN (BLOCKER); WP7-F02 OPEN — PARTIALLY VERIFIED
(HIGH)**, kizárólag a C3-02.5 közölt filesystem-részproof miatt.

## 2. Baseline és evidenciaforrások

### 2.1. Közvetlen baseline

| Tétel                       | Megfigyelés                                                                                                   |
| --------------------------- | ------------------------------------------------------------------------------------------------------------- |
| Branch                      | `feature/returns-repair-management`, elvárással egyezik                                                       |
| HEAD                        | `c8c01cae0df454674fd39c9f26a04015fa105c98`; az elvárt `c8c01ca` prefix egyezik                                |
| Kezdő Git status            | Egyetlen `?? .kiro/designs/rma-0004-c3-02-6-preflight-evidence-and-poc-readiness.md`; tracked/index diff üres |
| C3-02.6                     | Létezik, közvetlenül olvasva; SHA-256: `59c52b2e9a2f5981b99d1813593db92faf341d9393f878c0e2294dfee4239a55`     |
| Kapcsolódás nélküli eltérés | Nem találtunk; nincs branchváltás vagy Git-módosító művelet                                                   |
| Kezdő megőrzési leltár      | 935 fájl; az alábbi algoritmus digestje `9e15d9b9dcc5351006fd9f76c9cf2ae48998bd51a4568546309f3defb17a3b5e`    |

A leltár read-only `git ls-files -z --cached --others --exclude-standard`
egyedi, nem üres UTF-8 útvonalainak rendezése, regular file-onként raw-byte
SHA-256, majd Python `json.dumps(rows, ensure_ascii=False, separators=(',', ':'))`
UTF-8 bájtjainak SHA-256 értéke. Ez review-megőrzési ellenőrzés, nem futási
integritás-manifest; ignored/untracked-outside-inventory környezetre nem vonatkozik.

### 2.2. Forráskulcsok és bizonyítékhatár

Az alábbi relatív linkekhez a táblákban `L:338` alakban a baseline szerinti
1-alapú forrássor, dokumentumoknál fejezetszám tartozik. Ezek a findingok
visszakereshető hivatkozásai; a sorok későbbi implementáció után változhatnak.

| Kulcs | Közvetlenül vizsgált forrás és szerep                                                                                                                                  |
| ----- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| L     | [launch.ps1](../../tests/ProofOfConcept/RmaTransactionBoundary/launch.ps1): token/path/DACL, Phase A/B, child-start, cleanup                                           |
| R     | [run.php](../../tests/ProofOfConcept/RmaTransactionBoundary/run.php): manifest-stop, setup, T01–T14, output és shutdown                                                |
| P     | [Prototype.php](../../tests/ProofOfConcept/RmaTransactionBoundary/Prototype.php): explicit config/PDO, executor-owned root, caller guard, retry                        |
| W     | [worker.php](../../tests/ProofOfConcept/RmaTransactionBoundary/worker.php): T08 külön runtime process, secret-tilalom, pipe-protokoll                                  |
| S     | [schema.mysql](../../tests/ProofOfConcept/RmaTransactionBoundary/schema.mysql): öt synthetic InnoDB tábla, trigger és `poc_post`                                       |
| H     | [evidence/results.json](../../tests/ProofOfConcept/RmaTransactionBoundary/evidence/results.json): történeti agent-recorded ACL-stop, 14 BLOCKED eset és source-hash-ek |
| D     | [PoC README](../../tests/ProofOfConcept/RmaTransactionBoundary/README.md): Phase A/B és threat/cleanup határok                                                         |
| C01   | [C3-01](rma-0004-c3-01-transaction-integrity-proof-plan.md), §5–9 és §12–15: Model C, proof/authorization, canonical V-kapcsolatok                                     |
| C02   | [C3-02](rma-0004-c3-02-transaction-boundary-poc-results.md), §2, §4–12: környezeti stop, prepared prototype és korlátok                                                |
| C022  | [C3-02.2](rma-0004-c3-02-2-windows-acl-diagnostic-results.md), §5, §8–12: restricted-token diagnosztika, történeti native exit 5                                       |
| C024  | [C3-02.4](rma-0004-c3-02-4-launcher-hardening-results.md), §2–3, §6–13, §15–16: approved-scope hardening és hiányzó runtime próbák                                     |
| C026  | [C3-02.6](rma-0004-c3-02-6-preflight-evidence-and-poc-readiness.md), §3–8: operátori részhalmaz, G01–G12, readiness                                                    |
| WP7   | [WP7](rma-0003c2-wp7-verification-migration-readiness.md), §6, §12–16: finding/closure és provisioning határ                                                           |

Kapcsolatok: L Phase A után indítaná a MySQL-t és R-t; R P-t használ és
S-t telepít; T08 W-t indít runtime-only configgal; R és L ugyanazt a H
létezését tiltja. R ma fixen ugyanoda írná az új eredményt. A H nem a
C3-02.5 eredményfájlja. C026 §4 operátori közlés, nem független reprodukció.
Ebben a feladatban csak Git/fájl/hash/statikus megfigyelés történt.
Windows API-, hypervisor- és MySQL-option támogatás a javasolt új profilra
nem igazolt; az exact platform capability review és runtime próba külön engedélyt igényel.

## 3. Workstream A — Manifest és evidence integrity

### 3.1. Tényleges mechanizmus és findingok

| ID    | Megállapítás                                                                                                                                                                        | Forrás                                                |
| ----- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------- |
| A-F01 | A H saját megjelölése agent-recorded launcher failure, nem DB-runner output; 0 executed, 14 BLOCKED                                                                                 | H:2, H `attempts`, `counts`, `tests`; C02 §2/6/8      |
| A-F02 | H `source_sha256` hat fájlt nevez meg: L, P, README, R, S, W. H saját hash-e nincs benne                                                                                            | H:267–275                                             |
| A-F03 | L csak `Test-Path`-ot végez H-ra; nem parse-ol és nem hasonlít source hash-eket. R ugyanezt `is_file`-lal tiltja még a try/finally előtt                                            | L:338–340; R:11–13                                    |
| A-F04 | R a finally-ben öt source raw-byte `hash_file('sha256', ...)` értékét mentené. Ez utólagos leltár, nem pre-execution trusted verification; README/vendor/bináris/config nincs benne | R:375–383; P:13                                       |
| A-F05 | C024 dokumentáltan csak L és D tartalmát változtatta. H régi L hash-e `1d9c8040…`, jelenleg `bc524d20…`; D régi `fb06106d…`, jelenleg `93d05c83…`. P/R/S/W hash-e H-val egyezik     | C024 §2–3; C026 §2; H `source_sha256`                 |
| A-F06 | Fix output, unchecked write, tényleges launcher cleanup előtt mentett cleanup-szöveg; sikeres PoC-ra is Preflight-címke kerülhet                                                    | R:110–130, R:370–394; L:416–425, L finally; C026 §5.5 |

H tehát történeti bizonyítékot őriz, és ahhoz executable-forrásokat azonosít,
de **nem enforce-olja** azok integritását. A hash-ek eltérnek a mai hardening
utáni inputtól; ezért történeti értékként nem hibásak és nem írhatók át.
Az új manifest létrehozása nem invalidálja a régit, ha külön namespace,
scope és baseline szerint történik. A régi fájl felülírása viszont elveszítené
az eredeti eredmény/hash kapcsolatát. Önálló SHA-256 lista nem hitelesítési
gyökér: aki kódot és manifestet együtt írhat, új hash-t is készíthet.

H `initial_git.existing_files_digest` a korábbi 922 fájlos inventory
összesített történeti azonosítója (C02 §1); a teljes akkori lista hiányában
ezt nem állítjuk most újraszámítottnak. L:45 `FileIdentity` a volume serial és
a file-index high/low részeiből készít azonosítót; ez a létrehozott könyvtár
objektumazonosságát ellenőrzi (L:259–270), nem a tartalmának SHA-256 hash-e,
és nem a H source-integrity ellenőrzésének része.

### 3.2. Két lehetséges megoldás

| Szempont           | A1 — Külön verziózott input manifest + append-only run evidence                                                                                                                                                    | A2 — Jóváhagyott, lezárt release bundle katalógussal és aláírással                                                                                    |
| ------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| Előny              | Kisebb launcher/runner adaptáció, Git-review-val visszakövethető; történeti H a helyén marad                                                                                                                       | Azonos immutable csomag vihető VM-be; erősebb eredetellenőrzés és reprodukálhatóság                                                                   |
| Hátrány            | Trusted digest/owner approval külső rögzítése és read-only staging kell; laza working tree nem elég                                                                                                                | Packaging, kulcskezelés, verifier és platformfüggő aláírási eljárás új infrastruktúra                                                                 |
| Security           | Exact allowlist + owner által külön rögzített manifest digest; hiány/extra/eltérés STOP. Mutable verifier önmagát nem hitelesíti                                                                                   | Trusted verifier és külön őrzött signing key szükséges; rossz signer/privileged host továbbra kívül a garancián                                       |
| Szükséges változás | L/R guard helyett történeti H hash-gate, új input-manifest verifier, explicit run-output paraméter, checked artifact writer/cleanup-finalizer; D leírás                                                            | A1 input/output elválasztásán túl build/export/import, signed-catalog verifier, approved certificate/key policy és offline staging                    |
| Verifikáció        | Jóváhagyott fixture bundle-ban egy source-byte eltérés, hiány, extra executable, manifest-csere, H-eltérés, symlink, meglévő output és write-failure mind blokkol; két azonos input ugyanazt a leltárdigestet adja | Ugyanezek plusz invalid signature, ismeretlen signer, régi/visszavont verzió és import közbeni byte-eltérés; exact bundle hash azonos mindkét oldalon |
| Rollback           | Új entrypoint/profil letiltása; előző approved bundle visszaállítása külön review-val. Régi H és új runok nem törölhetők; nincs régi output felülírás                                                              | Bundle aktiválás megszüntetése, előző jóváhagyott verzió kiválasztása; rossz kulcs esetén revocation és új review, nem unsigned fallback              |

**Ajánlás: A1**, külön VM-be importálható frozen bundle-ként; A2 későbbi
erősítés, ha rendelkezésre áll független signature-infrastruktúra. A1 elfogadása
csak akkor véd jogosulatlan drift ellen, ha a jóváhagyott manifest digestet
az owner a futtató által nem írható review/evidence csatornán rögzíti és
a trusted verifier indítását is e csatorna ellenőrzi. Ilyen trust anchor ma nincs.

### 3.3. A1 tervezett szerződés és ellenőrzés

A lehetséges új nevek **javaslatok, nem létező vagy most létrehozott fájlok**:
`integrity/inputs-v<N>.json`, `runs/<run-id>/...`. H az eredeti helyén marad,
várt SHA-256: `a2e52bc4f68efea95035caa8ec8ddcac9bf941ee13bf30f516f39312449bc9b5`.

Az input manifest mezői: schema/version, approved baseline és változásreferencia,
relatív canonical path, byte-size, raw-byte SHA-256, role (code/config/dependency),
exact allowlist, history reference és history digest. UTF-8 rendezett pathlista,
case-collision/duplicate/path traversal/abszolút path/reparse elutasítás;
line-endinget nem normalizálunk hash-elés előtt. A hash-lista szerializációját
és a manifest own digestjét külön pontosítani kell; self-hash mező nem szükséges.

Input-scope: L/P/R/W/S, README, `composer.lock`, a ténylegesen betöltött vendor
dependency closure, PHP/MySQL executable és szükséges DLL/plugin/ini/config
profil, új helper/verifier. Secret és runtime datadir nem input manifestbe való.
P:13 autoloadja miatt a három PHP-fájl hash-e önmagában nem teljes code identity.
Verziózott config-template hash-e és a secretmentes resolved-config hash-e
külön legyen; generált credential tartalma ne kerüljön publikus artifactba.

A verifier **create/init/DB-connect előtt** ellenőrzi H-t és a jelenlegi
inputokat a trusted expected digest ellen. R önálló/direct belépésnél is gate-el;
string environment flag nem elegendő authorization. Child-spawn/import után
ismételt ellenőrzés, read-only input bundle és azonos approved run descriptor
csökkenti a verify/use közötti változást. Same-user/admin manipuláció ellen
puszta utólagos rehash nem atomi garancia; B2/C2 ezt külön határolja.

Run-output: kriptográfiai unique ID, create-new cél, tilos overwrite/symlink,
külön source/config/history digests, token/instance metadata, nyers és redacted
logok, expected/actual/status per test. Checked write, close/flush/readback/hash,
az exportált másolat visszaellenőrzése szükséges. Process-/OS-crash elleni
teljes durabilityt nem állítunk. RUNNING és végső COMPLETE/FAILED/INTERRUPTED
rekord elkülönül; a child nem írja át a lezárt originalt. A supervisor a runner
eredményét és a tényleges cleanupot külön digestelt indexben kapcsolja össze.
Artifact-hiba nem lehet sikeres run; evidence-capture és teardown külön
finally-scope, hogy egy write-error ne ugorhassa át a server-stopot.

## 4. Workstream B — Phase B privilege separation

### 4.1. Jelenlegi korlátok

**B-F01:** L:120–140 tokent mér, de elevated Phase B-t nem tilt; L:363/375
`Start-Process` initializer/server és L:387 PHP hívás nem tartalmaz külön
execution identityt vagy privilege-reductiont. A szokásos útvonalon a launcher
security contextje továbbvihető; tényleges child-token measurement nincs.
Az öröklést ezért statikus kockázatként kezeljük, nem mért child-state-ként.

**B-F02:** Nem emelt, megfelelő jogú token statikusan nincs kizárva, de a
pozitív runtime nincs igazolva. C026 §4 elevated/unrestricted operátori
eredménye nem non-elevated proof; C022 §5 és C024 §11 restricted Codex-tokenje
nem ugyanaz a profil. A `ConfirmAuthorizedPoC` kapcsoló nem token-gate.

**B-F03:** A jelenlegi DACL az aktuális user SID egy FullControl ACE-jét és
opcionális SYSTEM/Administrators ACE-t engedi (L:182–201). Más dedikált SID
nem engedett. Azonos user SID nem emelt tokenje illeszkedhetne a user ACE-hez,
de restricted SID/MIC/owner/policy/effective access külön mérendő. Az elevated
Administrators owner egy nem emelt hívó owner-policyján elbukhat.

**B-F04:** Nincs handoff/import: L:259–305 a saját invocation creation/file-ID/
ownert várja, a Preflight az üres sandboxot törli. B1/B2 nem valósítható meg
pusztán a régi temp path újrahasználatával. Cleanup privilege boundaryt ma
csak a tárolt server handle/PID kezeli (L finally), külső supervisor nincs.

### 4.2. Opciók összehasonlítása

| Szempont               | B1 — Külön non-elevated operátori indítás                                                                                                                     | B2 — Dedikált least-privilege test identity                                                                                 | B3 — Kontrollált token reduction                                                                                                             |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| Security               | Elevated előkészítő sosem futtat DB/PHP-t; manuálisan indított non-elevated run friss Phase A-val. Ugyanazon SID hostadat-hozzáférése megmaradhat             | Külön standard account a választott VM-ben; nincs hostadat/network/secret. DB-root csak disposable DB-role, nem OS-admin    | Reduced/filtered token nem automatikusan teljes izoláció; retained privilege, restricting SID, handle/environment öröklés mind ellenőrizendő |
| Komplexitás            | Közepes; aktuális invocation/owner lifecycle miatt külön entrypoint és gate szükséges                                                                         | Közepes–magas; provisioning, identity/ACL és supervisor policy kell                                                         | Magas; trusted native broker, token/desktop/job/API és hibakezelési security review                                                          |
| Windows-kompatibilitás | PS7.4+/exact Windows profil; normál interaktív shell, UAC állítás nélkül                                                                                      | Választott Windows guest standard account; host-hypervisor külön operátori szerep                                           | Exact Windows/API capability és privilege követelmény tisztázandó; nincs most bizonyított implementáció                                      |
| Credential             | Saját session, nincs password-argumentum vagy transcript-secret; DB-secret új                                                                                 | Account login operator/approved secret store felől; nincs hardcoded/jelszó-CLI; teszt-DB-secret külön                       | OS-password esetleg elkerülhető, de token handle érzékeny; DB-secret/handle öröklést szűrni kell                                             |
| Sandbox access         | Előkészítő csak reviewed inputot ad át; run owner maga hozza létre friss private rootját. Privileged root-handoff csak külön manifest/file-ID/ACL protokollal | Dedikált SID saját run-root; read-only code és külön writable data/log/output. Jelenlegi SID-policy átvezetése külön review | Csökkentett token actual AccessCheck + create/read/write/delete próba; restricted tokennél a sima user ACE elégtelen lehet                   |
| Cleanup                | Run user saját processzét/fájljait kezeli, retained root külön operátori recovery                                                                             | Ugyanazon standard run owner vagy előre regisztrált VM-supervisor; host csak exact VM run ID-t kezel                        | Brokernek start előtti job/handle ownership és bounded stop kell; nincs privilege-upgrade fallback                                           |
| Evidence quality       | Parent/child token külön mérve; handoff előtti és utáni hash; SID-től független run ID                                                                        | Account/profile/guest ID és child-token + DB-principal trace jól elkülöníthető                                              | Primary/thread token, enabled/deny-only/restricted/privileges/integrity és inherited handles teljes bizonyítása kell                         |

**Ajánlás: B2 C2-vel.** VM provisioning lehet külön privilegizált adminfeladat,
de DB/PHP csak standard guest run accountból indulhat. UAC kikapcsolás vagy
host operátori elevation nem része a tervnek. B1 használható kisebb
alternatívaként, ha friss non-elevated saját sandbox és WAMP-ACL/network
kizárás külön bizonyított; automatikus fallbackként nem.

A privileged előkészítés és unprivileged execution elválasztható **új
szerződéssel**, de elsőként nincs live elevated sandbox-handoff. Az előkészítő
csak secretmentes frozen code/environment profilt provisionál, a run user
maga hozza létre az új datadirt. Ha owner/hozzáférés ezt nem teszi lehetővé,
STOP; nem take-ownership, nem broad grant, nem automatikus elevation.
Más SID-ről átadott root esetén előre deklarált owner/ACL allowlist és
signed/digestelt handoff-ticket, path/file-ID/expiry/one-use binding és
mindkét identity valódi hozzáférési próbája kellene: ez nem jelenlegi képesség.

### 4.3. Kötelező execution és cleanup gate

Új Phase B default-policy: `elevated=false`, nincs enabled Administrators,
nincs SYSTEM identity, nincs váratlan impersonation; exact approved execution
SID és szükséges privilege-profile. Ismeretlen/olvashatatlan token: STOP még
initializer előtt. Integrity SID önmagában nem döntési alap. Parent és minden
MySQL/PHP/worker child actual tokenje naplózandó; a child nem önbevallásos
`unprivileged=true` mezővel bizonyít.

Ha emelt futásra valaha külön engedély születne, az külön reviewed profilhoz,
runhoz és owner/security jóváhagyáshoz kötött kivétel legyen; egyszerű CLI
kapcsoló nem adhat kivételt. A jelen ajánlás nem kér ilyen kivételt.
Job/supervisor tervezésnél start előtti child containment, handle/process
creation-time és image identity, exact run PID-lista, bounded wait szükséges.
Pid újrahasználat miatt bare PID utólagos kill-lista nem elég. Ha elkülönítés
nem bizonyítható: BLOCKED, nincs DB initialization, retained evidence, owner jelzés.

## 5. Workstream C — MySQL isolation és cleanup

### 5.1. Meglévő kontrollok és hiányok

| ID    | Megfigyelés                                                                                                                                                                                                             | Forrás                                                  |
| ----- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------- |
| C-F01 | Fresh random root, local NTFS/canonical/reparse/file ID kapuk vannak; hostile concurrent same-user actor elleni atomi törlés nincs                                                                                      | L:59–118, L:168–201, L:259–305; D Cleanup               |
| C-F02 | Explicit binary path, version 8.4, `--no-defaults`, fresh datadir, loopback/owned listener tervezett; a port kiválasztása és MySQL bind között elengedett socket miatt race lehetséges                                  | L:341–382                                               |
| C-F03 | Nincs külön config file; CLI argumentumlista van. Nincs WAMP actual datadir allow/deny inventory vagy process OS-hozzáférési korlát; névminta nem adatvédelmi boundary                                                  | L:79–92, L:360–384                                      |
| C-F04 | P explicit DSN és nem persistent/native PDO; R admin metadata queryvel datadir/port/version/system schema ellenőrzést végez CREATE előtt. Saját server UUID-t ekkor csak rögzíti, nem független expected UUID ellenőrzi | P:28–64; R:60–75                                        |
| C-F05 | L existing RMA configot tilt, de PHP környezete nem teljes allowlist; W-nek R szűk env-et ad. P app `.env`-et nem bootstrapol, vendor autoloadot igen                                                                   | L:340, L:384–392; R:218–226; P:13/28                    |
| C-F06 | Launcher finally saját servert állítana le, R SHUTDOWN-t kér; initializer/worker/parent-kill/OS-failure teljes felügyelete nincs bizonyítva, output-write kivétel átugorhatja R shutdownját                             | L:363–382 és finally; R:212–264, R:374–390; C024 §12–13 |

### 5.2. Két izolációs megoldás

| Szempont          | C1 — Temporary standalone MySQL                                                                                   | C2 — Disposable container / virtualized instance                                                                                                  |
| ----------------- | ----------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| Safety            | Friss datadir és explicit endpoint jó wrong-target védelem; host OS-identity WAMP-jogait önmagában nem korlátozza | Külön guest storage/network, host mount és credential nélkül erősebb adat-elválasztás; hypervisor/admin kompromittálás kívül marad                |
| Reprodukálhatóság | Exact native MySQL/PHP/DLL/ini/vendor digest és OS-build kell                                                     | Pinned guest image/snapshot + exact package/config digest és provisioning record; image drift ellen ugyanúgy A-gate                               |
| Windows/WAMP      | Legközelebb a jelenlegi Windows/NTFS/native forráshoz                                                             | Elsődlegesen Windows VM, így NTFS és PS source vizsgálható. Linux container új OS/driver/path/ACL profilt jelent, eredménye nem Windows ACL proof |
| Operációs költség | Kisebb, de host path/ACL/network confinementet külön kell megoldani                                               | Magasabb: platform/image/storage/import-export owner; rendelkezésre állás nincs most felmérve                                                     |
| Cleanup           | Own child/process tree és datadir; hostcrash után lehet orphan/retained secret                                    | Exact run-owned VM/container stop és ephemeral disk destroy; host-failure után inventory/reconciliation kell                                      |
| Evidence          | Native Windows exact profile, de erős hostadat-kizárás külön igazolandó                                           | Guest/process/endpoint/volume identity jól köthető run ID-hoz; export readback/hash és host supervisor journal szükséges                          |

**Ajánlás: C2, eldobható Windows VM**, nem hoston publisholt általános MySQL
service. Hoston nincs WAMP mount, drive/clipboard/credential passthrough,
host-network vagy host-management socket a guestben. VM network policy
host WAMP/production irányban deny, futás közben lehetőleg hálózat nélkül;
MySQL és PHP a guest loopbacken kommunikál. C2 nem jelenlegi L-kapcsoló:
külön supervisor/import/export/environment-profil és a run-output adaptációja kell.
Linux container csak külön jóváhagyott alternatív profil, nem néma helyettesítés.

### 5.3. Kötelező környezeti szerződés

**Filesystem:** egy runhoz egy friss guest disk/root és egy random unique datadir;
atomikus create-new, exact canonical parent, volume/file ID, junction/reparse
és existing-path tiltás. Source read-only, data/log/config/output külön. A
provisioning explicit rögzíti, hogy host WAMP datadir nincs mountolva és nem
érhető el; a standard identitynek nincs host login/secret. C1 esetén tényleges
WAMP datadir/backup/export útvonalak read-only operátori inventoryja és explicit
access-denial próba kell; pathnévből nem találunk ki ilyen listát.

**Explicit config:** új run-specifikus secretmentes `my.ini` javasolt, rögzített
hash-sel és canonical path-tal. Exact MySQL8.4 binaryhoz review-zott exclusive
config-file mode, megfelelő első argumentum/option semantics, ellenőrzött
resolved settings szükséges. Ez a jelenlegi `--no-defaults` CLI-profil tudatos
adaptációja; a két módot nem keverjük feltételezetten. Az exact build támogatását
külön ellenőrizni kell; config hiány/parse/unknown option esetén STOP, nincs
default option-file vagy WAMP-config fallback. Konfigurálandó scope: basedir,
datadir, error/PID/temp/log helyek, loopback/nonstandard port, X endpoint tiltás,
local-infile tiltás, secure-file-priv tiltás, binlog/egyéb endpoint és plugin
profil explicit kezelése. Init bootstrap secret külön private file, nem publikus ini.

**Process:** approved explicit absolute MySQL/PHP binary és dependency hash-ek,
guest execution SID és actual child-token gate. Initializer és server külön
run handle/creation time/image/datadir binding; init exit és bounded timeout.
Supervisor containment child start előtt; nincs WAMP service-control, name-based
kill vagy automatikus port fallback. Port-claim race esetén startup BLOCKED;
csak külön új run kaphat új endpointot. Unexpected exit megszakítja a PHP-t és
tiltja a reconnectet másik instance-ra.

**Connection:** fresh allowlisted child env; app DB\_\*/DATABASE_URL/default
connection config, PHP auto-prepend és nem jóváhagyott ini/dependency feloldás
nem öröklődhet. Pontos kezelésük platform review tárgya, puszta env-törlés nem
mindenkori config-proof. P explicit guest loopback/port/DB/user contractja
megmarad, credential csak új runhoz generált. R:60 előtti admin connect is
veszélyes boundary: **független OS/supervisor mapping előzze meg**, hogy ne
küldjön tesztsecretet tetszőleges loopback listenernek.

Független supervisor a run-owned PID/creation time/image/endpoint és canonical
datadir egyezését rögzíti, inicializálás után saját ellenőrzött guest fájlból
várt server UUID-t köt a runhoz. Első DB-olvasások csak identity probe-ok;
CREATE/DDL/DML előtt R hasonlítsa össze a várt és actual UUID/datadir/port/version,
system-schema allowlist és config-profile értékeket. Ugyanazt a DB-választ
kétszer lekérdezni nem független azonosság. Mismatch/hiány/extra schema esetén
STOP, sem connection fallback, sem schema cleanup az idegen serveren.
Runtime és worker kapcsolatot is az approved instance descriptorhoz kell kötni.

### 5.4. Failure és cleanup protokoll

| Esemény                               | Tervezett kötelező reakció és evidence                                                                                                                                                                 |
| ------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Normál befejezés                      | Test artifact lezárás; saját writer/worker disconnect; saját server graceful shutdown, bounded wait; export/readback; exact run-root/guest storage ellenőrzött eltávolítása; külön cleanup verdict     |
| SQL failure                           | SQLSTATE/vendor/original exception és observer snapshot; csak saját root abort/discard, tilos unrelated rollback vagy wrong-instance DROP; nincs blind replay; ezután ugyanaz a teardown               |
| PHP fatal/nonzero/hang                | Külső supervisor észleli, runtime worker containment leáll, server saját handle alapján stop; partial evidence INTERRUPTED/FAILED, nem PASS                                                            |
| Initialize/startup failure            | Runner nem indul; initializer és esetleges server child is tracked; init/server log védett export; identity alapján partial root cleanup vagy quarantine                                               |
| Deadline/timeout                      | Előre rögzített init/start/test/total/cleanup deadline; új művelet tiltott; graceful majd own-tree forced stop; unknown transaction outcome és cleanup külön rögzített                                 |
| Parent launcher megszűnik             | Parenttől független supervisor/containment és run journal követi a childot; ennek működését későbbi kill-próba bizonyítsa. Puszta finally nem garancia                                                 |
| Megszakítás/host OS crash/áramvesztés | Azonnali cleanup/durable utolsó log nem garantálható. Restart után journal alapján reconciliation, retained disk/process quarantine, secrets lezárása; korábbi run nem minősül automatikusan tisztának |
| Export vagy delete failure            | Nincs success override; retained resource ID és ok; only owner recovery, nincs broadened ACL/path/name kill. Evidence írási hiba nem gátolhatja a kontrollált stop-kísérletet                          |

Törlés előtt exact run resource identity és descendant no-reparse ellenőrzés,
process exit és lezárt artifact. Hypervisor-szinten VM/disk ID törlése nem
könyvtárnév-glob alapján történik. Cleanup-protokoll újrapróbálható azonos run
journalból, de nem foghat meg közben újrahasznált PID/pathot. Osztott owner:
QA futási journal, DBA instance closure, platform owner VM/disk cleanup,
security secret retention. Az OS/hypervisor/admin támadót és fizikai failure-t
nem teszi lehetetlenné a terv; e residual risk explicit elfogadandó.

## 6. T01–T14 readiness mátrix

Közös jelölések: **Q** = `poc_commands` + `poc_business` + `poc_evidence`;
**M** = `poc_mutex`; **U** = `poc_caller_sentinel`; **API** = `poc_post`,
`poc_complete` trigger, hat immutable UPDATE/DELETE trigger (R:84–96).
**E** = R:60–107 friss DB/setup, actor101 runtime, locked definer, native PDO,
autocommit observer, A/B/C gate és approved output. **K** = saját root/connection/
cursor lezárása, case snapshot, végül run-owned server/datadir teardown; nem
alkalmazási teszt-wrapper. Minden sor **prepared source / NOT EXECUTED**,
közös blocker A/B/C/output és a két történeti manifest-stop.

| ID / forrás | Intended invariant                                                    | Required schema objects               | Expected setup                                             | Expected observation                                                    | Expected cleanup                                                | Current implementation status                                          | Dependencies                              | Remaining blockers                                                                                                     |
| ----------- | --------------------------------------------------------------------- | ------------------------------------- | ---------------------------------------------------------- | ----------------------------------------------------------------------- | --------------------------------------------------------------- | ---------------------------------------------------------------------- | ----------------------------------------- | ---------------------------------------------------------------------------------------------------------------------- |
| T01 R:132   | Executor root az egyetlen commit owner                                | Q/M/API                               | E, executor, precommit callback                            | Depth1/PDO-active; observer Q=0 commit előtt, Q=1 utána; canonical hash | K, executor disconnect                                          | Assertionök és trace előkészítve                                       | E; P execute/state                        | Közös gate; actual commit/observer proof hiányzik                                                                      |
| T02 R:144   | Fault után nincs partial command                                      | Q/M/API                               | E; after_business fault                                    | SQLSTATE45000, Q=0, root rollback trace                                 | K; failed lease discard                                         | Fault és zero-triplet check van                                        | E; P error unwind                         | Közös; explicit végső root-state/lock és mysql vendor evidence erősítendő                                              |
| T03 R:151   | Nested caller nem veszíti el rootját/savepointját                     | Q/API/U                               | E; caller depth2, sentinel3, factory-call tiltás           | Rejection CALL/factory előtt; U committed, Q=0, savepoint használható   | Caller saját commit/rollback, disconnect + K                    | `refused` bármely RuntimeExceptiont elfogad; factory sentinel is ilyen | E; R:48; P caller registry                | Exact rejection reason és factory-called=false mérés kell; savepoint usability explicit oracle                         |
| T04 R:168   | Raw caught-error+COMMIT nem őriz partial írást és nem rollbackeli U-t | Q/M/API/U                             | E; raw root + sentinel4; no-BEGIN variáns                  | Mindkét fault45000; U megmarad, Q mindkét key-re 0, caller root él      | Caller saját commit; raw PDO close + K                          | Két fault source megvan, `a/b` kódok nincsenek külön assertelve        | E; API; native CALL drain                 | Exact error-code és caller/savepoint trace; raw no-BEGIN sikerág határát külön kell rögzíteni                          |
| T05 R:181   | Protected DML/DDL/system schema deny                                  | Q/API                                 | E; T01 tény; session flag bypass kísérlet                  | 1142/1044, változatlan Q és érték/hash                                  | Runtime PDO close + K                                           | Deny-loop és darabszám van                                             | E; T01 siker; actual grants               | Minden védett table/verb lefedettség review; row-value előtte/utána is kell, count önmagában kevés                     |
| T06 R:194   | Public complete API siker, helper deny                                | Q/M/API + `poc_private_helper`        | E; public EXECUTE igen, helper nem                         | Runtime siker Q=1, helper1370                                           | K                                                               | Success/helper check megvan                                            | E; R setup grants/helper                  | Grant/definer exact effective proof nincs; közös gate                                                                  |
| T07 R:203   | Replay egyszer, changed payload conflict                              | Q/M/API                               | E; ugyanaz key/hash kétszer, majd amount eltér             | Replay flag, Q=1; conflict45000 és változatlan amount/hash              | K                                                               | Exceptiont vár, conflict SQLSTATE külön nincs assertelve               | E; idempotent API                         | Exact conflict reason + értékazonosság, nem puszta count                                                               |
| T08 R:212   | Két session actual lock-wait és egy hatás                             | Q/M/API; performance_schema wait view | E; parent uncommitted, külön W, pipe go barrier            | Külön connection ID, wait ténylegesen látható, worker replay, Q=1       | Parent rollback ha aktív, pipe close, worker terminate/reap + K | Külön process és wait-probe van                                        | E; W:7–34; proc_open, P_S instrumentation | Worker exit/stderr/EOF és deadline explicit bizonyítás; init/process tree supervisor; instrumentation capability       |
| T09 R:265   | Whole-root retry friss sessionnel 1205-re                             | Q/M/API                               | E; admin mutex root, runtime timeout1s, retry-hook release | Exact1205/SQLSTATE, két külön session, failed root Q=0 és végül Q=1     | Admin root close, leases discard + K                            | Két entry/egy error mérés van; exact1205 külön nincs assertelve        | E; mutex; P:153 retry                     | Error trace vendor code és exact assertion; valódi1213 nincs ebben a tesztben                                          |
| T10 R:290   | UNKNOWN után lookup, nem második execute                              | Q/M/API                               | E; confirmed commit után mesterséges ack-vesztés           | Fresh primary canonical hash/Q=1; execute count1                        | Fresh PDO close + K                                             | Forrás eleve PARTIAL                                                   | E; P UnknownCommitOutcome                 | Valódi ambiguous COMMIT/network fault hiányzik; szimuláció címkéjét meg kell őrizni, nem upgrade-elhető teljes proofra |
| T11 R:305   | Evidence atomicity és immutability                                    | Q/M/API + immutable evidence triggers | E; mindkét fault; T01 evidence-re admin UPDATE/DELETE      | Mindkét fault Q=0, mutation45000 és T01 érték/hash változatlan          | K                                                               | Fault zero-triplet és admin deny van                                   | E; T01; privileged observer               | Fault exact SQLSTATE + eredeti T01 row/hash explicit összehasonlítás erősítendő                                        |
| T12 R:321   | Failed lease nem fertőzi a következő commandot                        | Q/M/API                               | E; bad majd good ugyanazon executoron                      | Külön connection ID; bad Q=0/good Q=1; entry depth0/PDO inactive        | K                                                               | Fresh-ID/count check van                                               | E; P disconnect/factory                   | Entry/root/final state és callback/cursor cleanup oracle pontosítása                                                   |
| T13 R:330   | Login-bound actor/hash deny, nincs effect                             | Q/M/API                               | E; actor102/session flags és hibás hash                    | Actor/hash45000, mindkét key Q=0, nincs bypass                          | Raw cursor/PDO close + K                                        | Actor check és hash-error van; T13_hash zero-row/kód assert hiányos    | E; S actor/hash; native cursor drain      | Hash-ág külön code/zero-triplet; egy synthetic login nem HTTP-user binding                                             |
| T14 R:346   | Cross-connection outer caller megőrzése                               | Q/API/U                               | E; registered caller depth1, sentinel14                    | Rejection exact okkal, Q=0, caller aktív, saját rollback után U=0       | Caller rollback/disconnect + K                                  | PARTIAL service-style szimuláció                                       | E; R:48; P registry                       | Broad refused helper; valódi app service/callback nincs, globális caller discovery nincs                               |

Az E setup nem futott; S telepíthetősége, effective grants és exact SQL/PDO
viselkedés nyitott, nem statikus PASS. A T01-függő T05/T11 nem mutathat PASS-t,
ha T01 bukik: explicit BLOCKED/SKIPPED dependency állapot kell. R:110–130 most
failure után tovább folytathatja a sorokat; a jövőbeli eredmény szétválassza a
precondition failure-t az invariant failure-től.

Test-oracle változtatás csak synthetic harness-scope lehet; nem RMA-business
logika. Root success **utáni explicit caller rollback**, valódi1213, mode0,
cache/afterCommit/pool, real unknown commit és actual service-integráció továbbra
külön proof-scope (C01 §7–9; R limitations). A szűk T01–T14 nem a canonical48
összes követelménye. A T10/T14 PARTIAL státusz sikeres részpróba esetén is megmarad.

## 7. Workstreamek közötti függőségek

Az alábbi sorrend aciklikus. A cleanup és evidence mechanizmusának **terve és
hibaági ellenőrzése előfeltétel**, tényleges futási végrehajtása későbbi állapot;
így nem követeljük meg a még el sem indított instance cleanupját az indítás előtt.

| Stage | Terület                                      | Előfeltétel                                                              | Engedett következő lépés / fail-closed határ                                               |
| ----- | -------------------------------------------- | ------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------ |
| S0    | Owner scope/platform/token/trust anchor      | E jelentés review-ja                                                     | Külön implementációs feladat; execution még tiltott                                        |
| S1    | Manifest integrity + evidence/cleanup design | S0, reviewed A1 schema/verifier                                          | Offline input-tamper/output-failure fixture validáció; nincs DB                            |
| S2    | Privilege separation és sandbox isolation    | S1 + B2/C2 provision review                                              | Külön engedélyezett guest/token/ACL/alternate-token/reparse próba; initializer még tiltott |
| S3    | Felügyelet + artifact sink readiness         | S2; run journal, timeout, child-containment, export write/readback próba | Supervisor-kill/cleanup fixture proof; lifecycle authorization külön                       |
| S4    | MySQL initialization                         | S1–S3 PASS és külön lifecycle engedély                                   | Friss guest datadir, initializer/server, actual identity/token; failkor own cleanup        |
| S5    | MySQL connection verification                | S4 + független OS endpoint/UUID mapping                                  | Csak identity probe, majd egyezés után synthetic schema/principal setup                    |
| S6    | Transaction execution                        | S5 + actual schema/grants + D-oracles + külön transaction engedély       | T01–T14; precondition dependency és hard stop rules; nem app DB                            |
| S7    | Evidence capture lezárása                    | S1 sink előre kész; S2-től folyamatos journal, S6 outcome                | Runner eredmény/metadata/redaction/export digest, nincs success artifact failure esetén    |
| S8    | Cleanup és reconciliation                    | S3 mechanizmus előre bizonyított; actual own resources S4-től            | Writers/children/server stop, artifact export, run disk destroy; külön cleanup result      |
| S9    | Független review                             | S7+S8 vagy explicit INTERRUPTED/retained állapot                         | Findingonkénti scoped assessment; nincs automatikus WP7 closure                            |

Ha A/B gate elbukik, S4 sem indulhat; ha C identity mismatch, nincs DDL/DML.
Cleanup minden S4 utáni hibaúton megkísérlendő, nem függ a test PASS-tól vagy
az artifact-write sikerétől. Új run csak korábbi retained erőforrások owner
reconciliationje után vagy explicit elkülönített kapacitásdöntéssel indulhat.

## 8. Mérhető elfogadási feltételek

Minden alábbi AC **PLANNED / NOT EXECUTED**. Az evidence tartalmazzon parancsot/
lépéseket, exact input/config/profile digests, UTC/run ID-t, expected/actual és
exit/status adatot; minden negatív próba külön engedélyezett fixtureben történjen.

| AC  | Mérhető feltétel és szükséges bizonyíték                                                                                                                                                                              | Owner / authorization határ                              |
| --- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------- |
| A01 | H byte-hash egyezik a §3.3 értékkel minden run előtt/után; régi C3 reportok változatlanok                                                                                                                             | Reviewer; implementációs megőrzés                        |
| A02 | Trusted owner digestből exact input-lista ellenőrzött; code/vendor/bináris/config hiány/eltérés/extra executable mind STOP, child init-count=0                                                                        | Security + backend; offline negative fixtures            |
| A03 | Source- és manifest-együttes csere sem fogadható el más trusted digest mellett; módosult/invalid verifier nem authorized                                                                                              | Security; trust-anchor review, nem önmagát igazoló hash  |
| A04 | Új run create-new output, history/output path ütközés STOP; readback/export hash egyezik; disk-full/write-error nem success és nem hagyja ki a teardown-kísérletet                                                    | QA/platform; artifact failure próba                      |
| B01 | Initializer/server/PHP/W actual token: approved SID, elevated=false, enabledAdministrator=false, nem SYSTEM; szükségtelen privilege nincs; hiányzó measurement STOP                                                   | Security; guest/token trial                              |
| B02 | Run user csak saját writable run data/log/outputra kap szükséges jogot, code read-only; más ordinary account tiltott; host WAMP storage/network elérhetetlen                                                          | Platform + security; access-denial trial                 |
| B03 | Elevated, impersonated, wrong-SID és unsupported restricted token input elutasított initializer előtt; nincs automatic elevation/fallback; kivétel külön approved profile nélkül nem létezik                          | Security/owner; negative trial                           |
| B04 | Parent-kill után child containment bizonyított; idegen PID/újrahasznált path nem érintett; cleanup unknown esetén retained resource ID és BLOCKED                                                                     | Platform; kill/recovery trial                            |
| C01 | Egy run egy fresh canonical datadir/guest resource; collision/reparse/mismatch elutasítás; WAMP nincs mountolva és host endpoint deny bizonyított                                                                     | DBA/platform; filesystem/network trial                   |
| C02 | Exact MySQL binary/config profile indul, default option/env/endpoint fallback nincs; config hibán initializer/server/runner nem folytat                                                                               | DBA; explicit option/config negative trial               |
| C03 | Független OS mapping + expected UUID/datadir/port/version egyezik az első metadata-probe-bal; wrong loopback listener/extra schema tiltott, CREATE/DML count=0                                                        | DBA/security; approved wrong-target synthetic fixture    |
| C04 | Normal, SQL/PHP failure, init/start failure, timeout és parent-kill mind külön cleanup artifact; own-server exit és disk/root removal bizonyított vagy explicit retained/unknown                                      | Platform/QA; lifecycle fault trial                       |
| C05 | Host WAMP megőrzés evidence: C2 no-host-mount/no-host-network policy + guest boundary; C1 alternatívában identity access-denial és reviewed before/after inventory. Hash-egyezés önmagában nem absence-of-write proof | Platform/security; existing WAMP connection/start nélkül |
| D01 | S1–S5 bármely failed/unknown gate esetén T01–T14 executed count=0; direct R/W entry nem kerülheti meg a run/instance contractot                                                                                       | Backend/QA; gate-negative fixtures                       |
| D02 | Mind a 14 sor saját expected/actual/principal/connection ID/depth/PDO/SQLSTATE/vendor/row-hash/cleanup/run-digest evidence-t ad; dependency esetén BLOCKED; nincs csendes hiányzó sor                                 | QA; külön transaction authorization                      |
| D03 | T01 precommit0/postcommit1; T02/T04/T11 partial0; T03/T14 saját caller sértetlen; T09 exact1205 és friss root; T13 hash/actor0 bizonyított                                                                            | Backend + DBA; transaction-run review                    |
| D04 | R/W csak approved instance-t és allowlisted DB-t használ; T10/T14 PARTIAL és további lefedettségi hiányok explicit; runner/launcher/cleanup verdict külön, exit0 nem full-proof állítás                               | QA/reviewer; evidence acceptance                         |

## 9. Kockázati regiszter

A likelihood kvalitatív tervezési becslés, nem mért előfordulási arány.
A forráshivatkozás a jelenlegi kontrollt/hiányt, nem a jövőbeli garanciát támasztja alá.

| ID   | Risk                                                   | Likelihood                                         | Impact                                      | Mitigation                                                                                    | Residual risk                                | Owner decision                                      |
| ---- | ------------------------------------------------------ | -------------------------------------------------- | ------------------------------------------- | --------------------------------------------------------------------------------------------- | -------------------------------------------- | --------------------------------------------------- |
| RK01 | Manifest drift vagy history átírás                     | Magas: dokumentált source drift                    | Hamis integritás/provenance                 | A1 frozen input/history split, trusted digest, tamper negative; A-F02–05                      | Trusted owner/verifier kompromittálás        | Security: trust anchor és retention                 |
| RK02 | Elevated process execution                             | Magas a jelenlegi elevated operátori profilból     | Hostadat/OS-jog kiterjedése                 | B2 child-token gates, C2; B-F01–04                                                            | Guest admin/hypervisor kontroll              | Owner: B2, nincs elevated kivétel                   |
| RK03 | Wrong MySQL instance                                   | Közepes                                            | Idegen DB módosítás/secret küldés           | OS endpoint mapping connect előtt, UUID/profile probe DDL előtt; C-F02/04                     | Privileged endpoint-spoofing                 | DBA/security: independent observer                  |
| RK04 | Datadir collision                                      | Alacsony random névvel, de runtime nem bizonyított | Meglévő adat törlés/írás                    | Atomic create-new, canonical file-ID + guest disk ID; C-F01                                   | Privileged concurrent replacement            | Platform: approved root/guest policy                |
| RK05 | Junction/reparse attack                                | Közepes shared host parentnél                      | Wrong-path traversal/delete                 | B2/C2, reparse negative és repeated identity; L:259–305                                       | Path-check/delete TOCTOU                     | Security: hostile actor scope                       |
| RK06 | Orphaned MySQL/worker                                  | Közepes                                            | Futó privileged process/retained locks      | Independent supervisor/job + resource journal; C-F06                                          | OS/power loss utáni reconciliation szükséges | Platform: recovery owner/deadline                   |
| RK07 | Partial cleanup                                        | Közepes                                            | Secret/datadir maradvány                    | Separate cleanup result, quarantine, owner-only recovery; L finally/R finally                 | Crash/backup/disk snapshot retention         | Owner: retention és recovery SLA                    |
| RK08 | Evidence corruption/loss                               | Közepes                                            | Hamis PASS vagy review-képtelen run         | Checked output/readback/export/digest; A-F06                                                  | Last log elveszhet OS-crashkor               | QA: store/access/retention                          |
| RK09 | Credentials exposure                                   | Közepes                                            | Test-admin vagy host credential kiszivárgás | Fresh secrets, env allowlist, no host secrets, private bootstrap/redaction; L:358–391, W:9–11 | Same-user memory/dump/VM snapshot            | Security: secret lifecycle és artifact redaction    |
| RK10 | False-positive transaction results                     | Magas a bizonyítatlan oracles miatt                | WP7 jogtalan closure                        | §6 exact reason/code/row assertions; PARTIAL címkék; R:48/151/265/330                         | Szintetikus scope nem teljes domain          | Backend/QA: oracle review és closure scope          |
| RK11 | Unsafe default config/environment                      | Közepes                                            | WAMP/profile keveredés                      | Explicit config-mode/env/binary dependency gate; C-F03/05                                     | Platform-specific option/loading eltérés     | DBA: exact tested build/profile                     |
| RK12 | VM/container hamis izoláció host mount/network mellett | Közepes, környezet ismeretlen                      | WAMP elérhetővé válik                       | C2 no-share/no-host-network policy és actual denial proof                                     | Hypervisor/host-admin támadó                 | Platform/security: image és host-integration tiltás |
| RK13 | Blanket PASS a launcher exit0-ból                      | Magas a mai címkézés mellett                       | PARTIAL/cleanup failure félreértelmezése    | Külön runner/cleanup/supervisor verdict és complete-case inventory; A-F06                     | Reviewer értelmezési hiba                    | QA: acceptance policy                               |

## 10. Javasolt implementációs sorrend

1. **Owner review és külön implementációs scope:** A1/B2/C2, exact Windows VM
   profil, felelősök és trust anchor. ADR/C1/business döntés változatlan.
2. **A1 offline implementáció:** történeti hash preservation, trusted input
   verifier, explicit run-output, checked writer/finalizer és verdikt-szétválasztás;
   csak külön engedélyezett synthetic input/output hibapróbákkal. H nem regenerálható.
3. **B2/C2 provisioning és process-supervision:** standard guest identity,
   frozen import, no WAMP share/network, token gates és előre kész recovery journal.
   Ez külön environment/ACL/account jogosultsági feladat, nem jelen engedély.
4. **Filesystem/token/supervisor verification:** complete JSON, wrong identity,
   alternate-token, reparse, output-error, child/parent-kill; a C3-02.5 korlátozott
   operátori közlést teljes artifacttal kiegészíteni, eredetit nem utólag kitalálni.
5. **Static harness/oracle/config review:** §6 hiányok, exact MySQL/PHP/profile,
   dependency BLOCKED és case-level finally. App/migration/üzleti módosítás kizárva.
6. **Új safety readiness review:** A/B offline és filesystem proof elfogadása,
   C lifecycle test konkrét engedélyezése; még nincs automatikus T01–T14 run.
7. **Külön engedélyezett lifecycle/identity próba:** init/start/connect/setup/
   cleanup negative és positive, host WAMP érintése nélkül; utána új assessment.
8. **Külön transaction execution authorization:** exact bundle/run/profile,
   stop/deadline/evidence/cleanup és T01–T14 scope rögzítése; végrehajtás csak
   actual A/B/C PASS után. WP7 findingonkénti review az eredmények birtokában.

Rollback minden szakasznál a következő gate tiltása és az utolsó approved
bundle/profil kiválasztása külön review-val. Az új evidence és a history
megőrzendő; retained DB/VM erőforrást csak saját identity alapján szabad
reconcile-olni. Rollback nem jelent alkalmazás-DB restore-t vagy WAMP-killt.

## 11. Nyitott kérdések

| Kérdés                                                                         | Felelős és szükséges válasz                                                     | Mit blokkol?                                                |
| ------------------------------------------------------------------------------ | ------------------------------------------------------------------------------- | ----------------------------------------------------------- |
| Van jóváhagyott Windows VM platform/image és provisioning kapacitás?           | Platform owner: exact OS/hypervisor/image digest, host integration policy       | B2/C2 implementáció; a terv elkészítését nem                |
| Ki őrzi a trusted manifest digestet és ki indítja a verified verifier-t?       | Security/project owner: külön hozzáférési határ és review-record                | A1 authorization, kód+manifest együtt cserélés elleni proof |
| Hol marad tartósan a full JSON/raw és redacted output?                         | QA/security: tár, access, retention, export és hash-chain felelős               | Evidence sign-off; C026 G05/G11                             |
| Mi az exact PHP/MySQL8.4 build, vendor/ini/plugin closure és config-file mode? | Backend/DBA: capability review és később actual negative proof                  | C profile és reprodukálhatóság                              |
| Mi a timeout/retry/recovery erőforrás-budget és SLA?                           | QA/platform/DBA konkrét értékeket hagy jóvá; nem találunk ki production policyt | S3/S4 lifecycle engedély                                    |
| Milyen threat model fogadja el privileged/same-user maradványkockázatot?       | Security/project owner: határ és tiltott host-integráció                        | B/C security acceptance                                     |
| Ha C1-et választják, melyek a tényleges host WAMP adat/backup útvonalak?       | Operátori read-only inventory és standard identity hozzáférési mátrix           | C1 csak ezen felül mérlegelhető; nem automatikus fallback   |
| Ki fogadja el T10/T14 PARTIAL és a canonical48 különbségét?                    | Backend/DBA/QA/WP7 owner: scoped proof és további tervek                        | Későbbi closure, nem a szűk kutatási terv                   |

Ezek owner/profil választások és implementációs előfeltételek. Runtime API/
platform képesség, child-token, wrong-instance, forced termination, DB-grant
és SQL/PDO viselkedés read-only statikus review-val nem bizonyítható; mind
külön engedélyezett későbbi vizsgálat. E hiányok nem fedhetők el feltételezett PASS-szal.

## 12. Kifejezett owner-döntések és engedélyhatárok

| Döntés | Konkrét jóváhagyandó eredmény/scope                                                       | Amit nem engedélyez automatikusan                                       |
| ------ | ----------------------------------------------------------------------------------------- | ----------------------------------------------------------------------- |
| OD1    | A1 history/input/run split, trusted digest, protected artifact store és retention         | H átírás/törlés vagy bármilyen DB-run                                   |
| OD2    | B2 standard guest execution identity, token policy; B1/B3 csak új külön review-val        | Host account/UAC/ACL módosítás e planning taskban; elevated Phase B     |
| OD3    | C2 Windows VM no-host-data/network profil, platform owner és kapacitás                    | Hoston WAMP-connect/start, tetszőleges container/mount/external server  |
| OD4    | Pontos L/R/P/W/S/D és új helper/config/synthetic test implementációs change-list          | App/service/business/migration/ADR-módosítás                            |
| OD5    | Külön filesystem/token/supervisor fault-verification feladat és megőrzési kritériumok     | MySQL-start vagy transaction execution                                  |
| OD6    | Implementáció utáni review alapján külön lifecycle, majd külön transaction scope/go-no-go | Scope-bővítés, canonical48 teljes igazolás vagy WP7 automatikus closure |

Az owner a tervet és a javasolt implementációs scope-ot review-zhatja; ebben
a feladatban egyik választás sincs elfogadottnak feltüntetve. A kapcsoló,
manifestben írt `authorized=true` vagy sikeres Preflight nem owner approval.
Az itt felsorolt későbbi engedélyhatárokat a felhasználói C3-02.7 feladat
kifejezett planning-only korlátozása és C01/WP7/C026 határai indokolják.

## 13. Végső readiness assessment és átadás

**PLAN COMPLETE — IMPLEMENTATION APPROVAL REQUIRED.** A terv tartalmazza
a szükséges opciókat, ajánlásokat, forráshivatkozásokat, 14 tesztes static
readiness mátrixot, függőségeket, mérhető AC-ket, recovery/rollback és owner
határokat. A környezeti választások nyitottak, végrehajtás nem engedélyezett.

| Terület | Jelen állapot                                                                                                                           |
| ------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| A       | Történeti H azonosítva; nincs pre-execution trusted source verification; A1 csak terv                                                   |
| B       | Elevated kockázat statikusan azonosítva; non-elevated B2 runtime nincs igazolva                                                         |
| C       | Izoláció/lifecycle csak prepared source és C2 terv; actual MySQL proof nincs                                                            |
| T01–T14 | 0 executed; változatlan harness nem futtatható; explicit oracle és evidence adaptáció szükséges                                         |
| WP7-F01 | **OPEN — BLOCKER**, ownership/rollback/raw CALL/driver proof hiányzik                                                                   |
| WP7-F02 | **OPEN — PARTIALLY VERIFIED — HIGH**, csak a közölt operátori filesystem-részhalmaz; teljes environment/manifest/fixture proof hiányzik |

A releváns DoD-változástípus dokumentáció, a security és tesztinfrastruktúra
elemzés tárgya. Célzott Markdown/relatív link/szerkezet/UTF-8/whitespace és
Git scope/megőrzési ellenőrzés tartozik az átadáshoz. PHP/Pint/PHPStan/app/
frontend/build/full/DB/transaction teszt nem futott: nincs executable változás,
a feladat ezeket nem engedélyezi. README/steering/ADR/playbook/workflow/memory/
backlog/i18n módosítás N/A: pontosan egy új terv engedélyezett, döntés/termék-
vagy üzleti működés nem változott. A terv review-készsége nem PoC-closure.

Dokumentációs validációs rekord: célzott Prettier-check; 13 számozott főfejezet,
T01–T14/AC/dependency/risk struktúra, relatív fájllinkek, UTF-8/fence/whitespace,
`git diff --check` és az untracked új fájl külön checkje; 935 kezdő inventory
fájl digest-egyezése az új terv kizárásával. A tényleges eredmény az átadási
összegzésben szerepel; nem runtime verification.

Csak ez az új jelentés keletkezett. C026 és minden meglévő forrás/manifest
változatlan. Nincs launcher/PoC process launch, account/token/UAC/ACL-változás,
sandbox-create, MySQL-start, DB-kapcsolat/SQL/PHP transaction teszt, migration,
staging/commit/push/merge/rebase/branchváltás. A munka itt megáll,
**projektgazdai review és implementációs jóváhagyás szükséges**.
