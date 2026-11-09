# RMA C3-02.4 — Windows Launcher Hardening & Sandbox Isolation Verification

Dátum: 2026-11-06. **PREFLIGHT BLOCKED**. Átadás: projektgazdai review.
Adatbázis-PoC: **NOT EXECUTED**. Migráció: **NOT MIGRATION READY**.

## 1. Vezetői összefoglaló

A launcher két explicit fázist használ; alapértelmezés a fájlrendszeres
`Preflight`. A numerikus SID grant `*SID` alakú. A tokenjog, tulajdonos,
védett DACL, canonical path, file ID és cleanup külön ellenőrzést kapott.
A pozitív izolációs kapu a jelenlegi restricted Codex-tokenben blokkolt:
`WRITE_DAC_DENIED_OR_UNVERIFIED`. Az ellenőrzés nem gyengült és nem történt
tokenmegkerülés, automatikus elevation vagy tulajdonosváltás.

A negatív runtime és memóriabeli ellenőrzések bizonyítékot adnak a kontrollált
megállásra; teljes pozitív Windows-izolációt nem igazolnak. MySQL-verziólekérdezés,
inicializálás, szerverindítás, DB-kapcsolat, SQL és PHP transaction runner nem futott.

## 2. Repository baseline

- Branch: `feature/returns-repair-management`.
- HEAD, induláskor és átadáskor: `e21e83da44e7ec2228efa6c5c6f8d1021340b73b`.
- Indulás: üres tracked diff és index, 17 untracked fájl.
- Leltár: hét C2 WP1–WP7 dokumentum, C3-01 terv, C3-02 eredmény,
  C3-02.2 ACL-diagnosztika, valamint hét PoC-fájl.
- C3-02.3 dokumentum az ellenőrzött inventoryban nem található; tartalmára nincs állítás.
- 933 induló Git-inventory fájl rendezett `(path, SHA-256)` listájának
  `SHA256(json.dumps(rows).encode())` értéke:
  `f158f6bb5c9a5c51a974c1b70b81eecb23b8b2675bedfa10c8bcd102def627a7`.
  A lista `git ls-files -z --cached --others --exclude-standard` UTF-8 útvonalaiból készült.
- Végállapot: 18 untracked fájl, üres tracked diff és index; ugyanaz a branch/HEAD.
  A két engedélyezett módosítás szintén eleve untracked volt.

Az összes további 931 induló fájl tartalma változatlan. A végső megőrzési
ellenőrzés kizárja az új jelentést, a launcher/README két kezdeti hash-ét
visszahelyettesíti, majd megköveteli a 933-as elemszámot és az induló digestet.

### Induló PoC SHA-256 leltár

| Fájl                  | SHA-256                                                            |
| --------------------- | ------------------------------------------------------------------ |
| launch.ps1            | `1d9c8040380974ddac2de1f90aa3d72aec00fb576c380a2a97eada1b1e420182` |
| README.md             | `fb06106d2068dd008ed4f91e81b7f3fa6dce1d28d6a300cf9dd2760a70a525b6` |
| Prototype.php         | `5490f552bf609532f1fc6e47c4f4c5adf3ffe26ad7ed83cc0734e974ec6044ae` |
| run.php               | `216d43003302e86f1657d955293496e7b89ef983845e0dc5c32c906170297986` |
| schema.mysql          | `f7bff2443aff7d462920f0e0e2fb984d4517521a6e1f042fd4a45b670f942855` |
| worker.php            | `0e2f3ec38aed9dcc430859eb09a6b457fe613c65a603af6b3e5ef0be461efe74` |
| evidence/results.json | `a2e52bc4f68efea95035caa8ec8ddcac9bf941ee13bf30f516f39312449bc9b5` |

A történeti manifest forráshash-ei a korábbi verziót azonosítják.
A hardening utáni launcherre nem követeljük meg a történeti hash egyezését;
a manifestet nem írtuk át és nem generáltuk újra.

## 3. Módosított és létrehozott fájlok

Kizárólag:

1. [launch.ps1](../../tests/ProofOfConcept/RmaTransactionBoundary/launch.ps1) — módosítás.
2. [README.md](../../tests/ProofOfConcept/RmaTransactionBoundary/README.md) — módosítás.
3. Ez a jelentés — új fájl.

A teljes hét PoC-forrás és az előzménydokumentumok review-ja megtörtént.
A PHP-, SQL-, üzleti-, migrációs és alkalmazáskód változatlan.

## 4. Eredeti ACL-hiba

A [C3-02 jelentés](rma-0004-c3-02-transaction-boundary-poc-results.md) az eredeti
launcher-megállást rögzíti; az eredeti natív kilépési kód nem vezethető le a
launcher általános kilépési kódjából.

A [C3-02.2 diagnosztika](rma-0004-c3-02-2-windows-acl-diagnostic-results.md)
külön reprodukcióban rögzítette az `icacls` **5** kódját és access-denied
kimenetét. A restricted token Administrators SID-je deny-only, effektív
WRITE_DAC hiányzott. Ez a reprodukció bizonyítéka; nem az elveszett eredeti
stderr utólagos helyettesítése.

## 5. Helyi operátori siker — felhasználói evidencia

A feladatban közölt, másik PowerShell-környezetben végzett kísérlet:

- high integrity, enabled Administrators;
- numerikus SID `*` nélkül: exit **1332**;
- `*SID:(OI)(CI)F` használatával: exit **0**;
- protected DACL, explicit operátori FullControl;
- owner Administrators, sikeres cleanup.

Ez felhasználó által közölt evidencia, ebben a feladatban nem reprodukáltuk.
Külön eredeti operátori transcript nincs a mostani jelentéshez csatolva.
A Codex-token ACL-képességét nem igazolja.

## 6. Launcher-architektúra

`Invoke-RmaFilesystemPreflight`: Windows/PowerShell 7.4+, natív token és
AccessCheck, parent gate, atomikus friss könyvtár, kezdeti ACL/owner,
WRITE_DAC gate, `icacls`, utóellenőrzés, dokumentált cleanup.

`Invoke-RmaTransactionPoC`: külön `PoC` mód és
`ConfirmAuthorizedPoC` megerősítés; azonos futásban sikeres Phase A szükséges.
Nincs automatikus átmenet a default preflightból. A meglévő történeti manifest
továbbra is blokkolja Phase B-t, amíg külön review nem rendezi az új futás
eredményének tárolását és az előzmény megőrzését.

A jövőbeli MySQL8.4/no-defaults/friss datadir/loopback/owned PID-listener és
runner-gate folyamat Phase B-be került. Módosult lifecycle-ja nem futott.
A [C3-01 Model C szerződés](rma-0004-c3-01-transaction-integrity-proof-plan.md)
és a PHP/SQL transaction implementation megmaradt: dedikált executor-owned
root, caller transaction védelem, változatlan szintetikus tesztállítások.

## 7. SID és diagnosztikai kimenet

A grant külön argumentum:
`[string]::Concat('*', $Sid, ':(OI)(CI)F')`.
A user SID a valódi process tokenből származik; nincs hardcoded gépnév,
felhasználónév vagy felhasználói SID. SYSTEM/Administrators well-known SID
a deklarált privileged policy része.

A [Microsoft icacls dokumentációja](https://learn.microsoft.com/en-us/windows-server/administration/windows-commands/icacls)
előírja a numerikus SID `*` prefixét. `/inheritance:r` az örökölt ACE-ket
távolítja el; `/grant:r` nem általános DACL-reset. Emiatt az összes megmaradt
ACE ellenőrzése kötelező.

Az OS system könyvtárából induló `icacls.exe` a pontos argumentumokat,
stdout/stderr nyers bájtjait base64-ben és OEM-dekódolt szövegét, azonnal
mentett exit code-ját, valamint exception type/message/HResult mezőit adja
vissza. Timeout és start exception külön szerepelhet. A natív parancsot
nem indító gate-nél exit/stdout/stderr `null`; nincs kitalált exit 5.

A Windows metadata lokálisan érzékeny lehet. A jelentésben a user/egyéb
lokális SID-ek és profilútvonalak szimbolikusak; a futási session a pontos
értékeket tartalmazza. Nincs credential, tokenérték, password vagy production
connection konfiguráció a Phase A bizonyítékban.

## 8. Owner/DACL policy

A policy a megfigyelt helyi siker eredményétől függetlenül deklarált:

- Owner: aktuális user SID; Administrators csak akkor, ha a token default
  ownere Administrators, és a csoport enabled, nem deny-only.
- A létrehozás utáni owner nem változhat; nincs automatikus tulajdonoscsere.
- Protected és canonical DACL. Pontosan egy explicit user Allow FullControl
  ACE, `(OI)(CI)`, propagation nélkül.
- Kizárólag SYSTEM/Administrators lehet opcionális privileged kivétel,
  ugyanilyen explicit FullControl ACE-alakkal.
- Egyéb SID, duplicate, deny, inherited, csökkentett vagy eltérő alakú ACE:
  STOP. A privilegizált kivételek nem kötelező és nem automatikusan hozzáadott ACE-k.
- A tényleges restricted token FullControl AccessCheck eredményének is
  igazoltnak és engedélyezettnek kell lennie; owner státusz önmagában nem elég.

A [restricted-token dokumentáció](https://learn.microsoft.com/en-us/windows/win32/secauthz/restricted-tokens)
szerint az enabled és restricting SID oldali ellenőrzésnek is sikerülnie kell.
Az [AccessCheck API](https://learn.microsoft.com/en-us/windows/win32/api/securitybaseapi/nf-securitybaseapi-accesscheck)
hívási sikere és az access-status külön mező; mindkettőt ellenőrizzük.

## 9. Izolációs threat model

Védett: véletlen rossz célpont, meglévő könyvtár újrahasználata,
örökölt/többlet ordinary hozzáférés, bizonyítatlan tokenjog melletti továbbhaladás.
A friss rootban ACL-ellenőrzés előtt sem credential, sem adatbázisfájl nem készül.

Az ordinary exclusion a protected allowlistből következik olyan idegen
tokenekre, amelyekben nincs operátori, SYSTEM vagy enabled-admin jogosultság,
owner/recovery vagy más override képesség. Más felhasználói tokennel tényleges
OS-belépési/read/write teszt nem történt.

SYSTEM/Admin recovery, Windows owner jogok, azonos userrel futó idegen folyamat,
privilegizált kompromittálás és rosszindulatú konkurens parent/path-csere
nem igazolt védelem. A canonical/file-ID/reparse ellenőrzések ismételtek, de
nem atomikus handle-relative törlési protokoll. Hostile parent esetén külön
tervezés szükséges. NTFS DACL nem általános sandbox vagy DB-integritási proof.

## 10. ACL01–ACL10 ellenőrzési mátrix

Evidencia: **R** = a 11. fejezet valódi fájlrendszeres futása; **U** = végrehajtott inline
memóriabeli/read-only ellenőrző; **S** = forrás/parser review.
A PASS mindig a sorban megadott szűk terjedelemre vonatkozik.

| ID    | Előfeltétel                                 | Elvárt eredmény                                   | Tényleges eredmény és evidencia                                                                                          | Státusz                                                    |
| ----- | ------------------------------------------- | ------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------- |
| ACL01 | Valós user SID; grant helper                | Külön `*SID:(OI)(CI)F` argumentum                 | U: pontos string/count egyezik; R: tervezett argumentum rögzítve. Natív SID-feloldás itt nem futott                      | PASS — argumentum; natív NOT EXECUTED                      |
| ACL02 | ACL-képes token és friss root               | Protected exact allowlist és effektív FullControl | U: user/admin-owner minták elfogadva. R: WRITE_DAC denied, privateDaclVerified=false                                     | BLOCKED — pozitív runtime                                  |
| ACL03 | Restricted token, WRITE_DAC denied          | Kontrollált STOP, nincs bypass                    | R: AccessCheck error 5, launcher exit 2, icacls nem indult                                                               | PASS — negatív runtime                                     |
| ACL04 | Hibás ACE-minták                            | Minden eltérés elutasítva                         | U: extra Everyone, deny, inherited, duplicate és hibás mask elutasítva. Élő hibás DACL-t nem állítottunk be              | PASS — unit; élő ACL NOT EXECUTED                          |
| ACL05 | UNC/repo/DB/custom parent; reparse          | Veszélyes parent elutasítva                       | U: UNC/repo/DB/custom elutasítva. R: valódi NTFS/no-reparse positive parent. Élő junction/symlink negatív próba hiányzik | PASS — részpróbák; reparse NOT EXECUTED                    |
| ACL06 | Már létező célpont                          | Nincs újrahasználat                               | U: ténylegesen létező temp parent mint cél: EXISTING_SANDBOX; S: atomikus CreateDirectory                                | PASS — read-only negatív; konkurens collision NOT EXECUTED |
| ACL07 | Nem létrehozott/idegen cél, saját üres root | Idegen cél STOP; saját ellenőrzött root törölhető | U: uncreated és repository cél elutasítva. R: canonical/file ID/owner/DELETE igazolva, üres root törölve                 | PASS — megadott scope                                      |
| ACL08 | Default Preflight, binárisok nélkül         | Nincs MySQL/SQL/PHP vagy Phase B                  | R: mode Preflight, phaseBStarted=false; U: sikeres Phase A stub után sem hívódik Phase B                                 | PASS — runtime negatív + control-flow unit                 |
| ACL09 | Blokkoló gate                               | Kódok, owner/DACL és hiba titkok nélkül           | R: snapshot, AccessCheck, planned args, error és cleanup rögzítve; icacls exit=null. Natív raw capture/timeout csak S    | PASS — stop evidencia; natív capture NOT EXECUTED          |
| ACL10 | Nem bizonyított izoláció                    | STOP; nincs implicit PoC                          | R: denied FullControl/WRITE_DAC, nincs Phase B; U: owner/protection/missing-user eltérés elutasítva. PoC mód nem futott  | PASS — negatív runtime/unit; teljes pozitív kapu BLOCKED   |

## 11. Tényleges runtime evidencia

Négy kizárólag fájlrendszeres preflight-hívás történt. Az első kettő a refaktor
utáni diagnosztika/exit-propagáció ellenőrzése, a harmadik a végső OS/version
guardokkal futott; ennek tool-visszajelzése csonkolt volt. A negyedik a végleges
forrás default módjának teljes, kompakt evidenciáját rögzítette.
Nem történt adatbázis-futási kísérlet.

Az első hívás JSON-ja launcher exit 2-t adott, a külső PowerShell
`& script` wrapper 1-et. A második/harmadik explicit `pwsh -File` és
azonnali exit-propagáció mellett a natív exit **2** lett. A végső default
függvényhívás explicit host exitje szintén **2**. Ezt nem szabad összekeverni
a történeti `icacls` exit 5-tel.

### R — végleges forrás, default Preflight

- UTC: `2026-11-06T15:26:20.7749667Z`; PowerShell `7.6.9`.
- Parent: `%LOCALAPPDATA%\Temp`; canonical local fixed NTFS, ACL support,
  no reparse; effektív FILE_ADD_SUBDIRECTORY engedélyezett.
- Root: `%LOCALAPPDATA%\Temp\RmaBoundaryPoc-ce0e45de3145d310fd98877f1be22033`.
- Volume: `D08670DE`; file ID: `00690000000D99CE`.
- Token: restricted=true; elevated=false; enabledAdministrator=false;
  elevationType=2; integrity SID=`S-1-16-12288`. Magas integrity önmagában nem
  teszi az admin SID-et enabled-dé és nem bizonyít WRITE_DAC-ot.
- Owner/default owner: aktuális user; initial protected=false, canonical=true.
- Hat örökölt Allow ACE; explicit/deny ACE nincs. A végső SDDL azonos a kezdetivel.
- Effektív ADD_SUBDIRECTORY: allowed=true; DELETE: allowed=true.
- WRITE_DAC: apiSucceeded=true, allowed=false, granted=0, win32Error=**5**.
- FullControl: apiSucceeded=true, allowed=false, granted=0, win32Error=**5**.
- `icacls` attempted=false, executed=false, exitCode/stdout/stderr/exception=null.
- Tervezett argumentumok, a lokális azonosítók szimbolikus helyettesítésével:

```text
<canonical-new-root>
/inheritance:r
/grant:r
*<actual-user-SID>:(OI)(CI)F
```

A nyers sessionben az utolsó argumentum pontos numerikus alakja:
`*S-1-5-21-175778158-3753295944-971442867-1001:(OI)(CI)F`.
Ez SID-metaadat, nem credential.

A hat örökölt ACE normalizált kezdeti/végső képe, változatlan sorrendben:

| Principal                | Mask       | Típus | Inherited | Inheritance / propagation |
| ------------------------ | ---------- | ----- | --------- | ------------------------- |
| helyi egyéb SID A        | `0x1301bf` | Allow | true      | 3 / 0                     |
| matching restricting SID | `0x1301bf` | Allow | true      | 3 / 0                     |
| helyi egyéb SID B        | `0x1301ff` | Allow | true      | 3 / 0                     |
| SYSTEM                   | `0x1f01ff` | Allow | true      | 3 / 0                     |
| Administrators           | `0x1f01ff` | Allow | true      | 3 / 0                     |
| operator                 | `0x1f01ff` | Allow | true      | 3 / 0                     |

```json
{
    "error": {
        "stage": "filesystem-preflight",
        "type": "System.Management.Automation.RuntimeException",
        "message": "WRITE_DAC_DENIED_OR_UNVERIFIED",
        "hresult": -2146233087
    },
    "cleanup": {
        "removed": true,
        "canonicalPathAndFileIdentityVerified": true
    },
    "parentAclUnchanged": true,
    "privateDaclVerified": false,
    "phaseBStarted": false,
    "verdict": "PREFLIGHT BLOCKED",
    "launcherExitCode": 2
}
```

A restricted token által elérhető Modify-jellegű ACE nem ad WRITE_DAC-ot.
A FullControl user ACE önmagában nem elég a restricting oldali ellenőrzéshez.

### U és S — elkülönített evidencia

17 inline unit/read-only eredménysor: SID argumentum, két jó owner-minta egy
eredménysorban, nyolc hibás DACL/owner eset, négy veszélyes parent,
létező cél, két cleanup-elutasítás; mind PASS a megadott szűk scope-ban.

További control-flow unitban a Phase A **memóriabeli stub**, Phase B pedig
hibát dobó sentinel volt: default Preflight a stub sikere után sem érte el
Phase B-t. A stub `privateDaclVerified=true` értéke nem valós ACL-siker.
Nem módosítottunk tesztfájlt és nem építettünk a launcherbe bypass kapcsolót.

PowerShell AST/parser: 0 hiba. A végső natív helper C# fordítása és a
token/AccessCheck/path/identity hívások valódi Phase A során működtek.
A report/README Prettier, relatív link/anchor, UTF-8/BOM, whitespace és
célzott sensitive-content ellenőrzést kap. `git diff --check` a tracked
diff mellett külön fájltartalom-vizsgálattal egészül ki, mivel a fájlok untrackedek.

## 12. Blokkolt és nem igazolt esetek

- A tényleges pozitív private DACL és operator FullControl kapu **BLOCKED**.
- A végleges launcher natív icacls-siker/raw output/timeout/start-error ága
  ebben a környezetben **NOT EXECUTED**.
- Alternatív ordinary Windows-token hozzáférési próba, élő junction/symlink
  negatív teszt, konkurens collision/path-replacement próba **NOT EXECUTED**.
- Phase B, T01–T14, MySQL-grantek, SQL és transaction atomicity **NOT EXECUTED**.
- Teljes alkalmazási quality gate, PHP/Pint, frontend/E2E és DB-suite nem
  futott: nem változott PHP/app/UI/DB; a feladat kizárólag célzott Phase A
  ellenőrzést engedélyezett, a DB/full teszteket tiltotta.

## 13. Fennmaradó biztonsági kockázatok

A privileged és same-user hozzáférés nem kizárt. A path/file-ID/owner gate-ek
nem atomikusak a törléssel, ezért ellenséges konkurens filesystem-actorral
szemben korlátozottak. Az OS-derived temp allowlist szándékosan konzervatív:
egyedi TEMP átirányítás blokkolhat. Windows owner/privilege/MIC/parent
jogviszonyok teljes modelljét egy DACL-snapshot nem helyettesíti.

A natív output OEM-dekódolás környezetfüggő; a base64 a nyers byte-evidencia.
A process-start/timeout/error ágak további operátori ellenőrzést igényelnek.
Phase B módosult lifecycle-ja külön review és futási proof nélkül nem tekinthető késznek.

## 14. Helyi operátori teendők

1. Review-zza ezt a diffet és a declared owner/DACL threat modelt.
2. Saját, ACL-képes, jóváhagyott helyi kontextusban, PowerShell 7.4+ alatt
   kizárólag az alábbi Phase A parancsot futtassa; nincs automatikus elevation:

```powershell
pwsh -NoProfile -File ./tests/ProofOfConcept/RmaTransactionBoundary/launch.ps1 -Mode Preflight
$preflightExit = $LASTEXITCODE
```

3. Őrizze meg a JSON stdoutot és azonnali exitet biztonságos helyi evidenciaként.
   Ellenőrizze protected/canonical DACL, owner policy, pontos ACE-k,
   effektív FullControl, cleanup és változatlan parent mezőit.
4. BLOCKED/FAILED vagy retainedPath esetén álljon meg; ne változtasson
   automatikusan owneren, parent ACL-en, és ne töröljön bizonyítatlan célpontot.
5. Külön környezetben/engedéllyel pótolja a hiányzó negatív filesystem- és
   ordinary-principal próbákat, a privilegizált recovery modell megőrzésével.
6. Phase B előtt új projektgazdai engedély, evidence-output megoldás review-ja
   és az összes izolációs/DB-gate szükséges. A történeti manifestet ne törölje
   a guard megkerülésére; preflight-siker nem Phase B engedély.

## 15. WP7-F01 és WP7-F02 következmények

[WP7](rma-0003c2-wp7-verification-migration-readiness.md) és
[C3-01](rma-0004-c3-01-transaction-integrity-proof-plan.md) változatlan.

- **WP7-F01 BLOCKER: OPEN.** A Model C transaction ownership, totality,
  caller rollback-védelem és konkurencia nem kapott új DB-runtime proofot.
- **WP7-F02 HIGH: OPEN.** A filesystem gate és evidencia javult; pozitív
  izoláció, provisioning, exact schema/grants és T01–T14 futási manifest hiányzik.

Accepted üzleti/architekturális döntés és történeti ellenőrzési eredmény nem
változott. Nincs migrations-ready vagy implementation-ready állítás.

## 16. Végső readiness és átadás

**PREFLIGHT BLOCKED** — a hardening review-ra átadva, pozitív izolációs
runtime igazolás nélkül. A DoD biztonsági pozitív/alternatív-principal kapuja
nyitott; a teljes PoC vagy migráció nem jelölhető done-nak.

A szűk fájlhatár, parser/unit/negatív runtime ellenőrzések és a dokumentáció
ellenőrizve; a hiányzó bizonyítékokat a 10–13. fejezet tartalmazza. Nincs PR, staging,
commit, push, merge, rebase, branchváltás, dependency telepítés vagy elevation.
Nincs MySQL/DB/SQL/PHP PoC-végrehajtás. Következő lépés: projektgazdai review,
majd engedélyezett helyi operátori Phase A bizonyíték.
