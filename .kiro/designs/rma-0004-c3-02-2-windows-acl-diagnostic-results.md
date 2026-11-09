# RMA-0004 / C3-02.2 — Windows Sandbox ACL Diagnostic Results

Verdikt: **ACL FAILED**. Dátum: 2026-11-05.
Egyetlen filesystem-próba történt. A létrehozási gate PASS, az actual ACL-beállítás FAIL.
A feladat nem engedélyezi a C3-02 MySQL PoC folytatását; ilyen kísérlet nem történt.

## 1. Repository baseline

- Branch: `feature/returns-repair-management`.
- HEAD: `e21e83da44e7ec2228efa6c5c6f8d1021340b73b`, változatlan.
- Kezdő tracked diff/index üres; 16 untracked fájl. Záráskor csak ez az új jelentés: 17 untracked fájl.
- NUL-delimited UTF-8 Git-path inventory: **932 meglévő fájl**, a nem ASCII nevű tracked fájlokkal együtt.
  A rendezett (útvonal,SHA-256) lista JSON-digestje:
  `15e1c2a9d48cd5263b17aabf4fb9801b3156ce62ad9bdfd380acd775f7e50db8`.
- [launch.ps1](../../tests/ProofOfConcept/RmaTransactionBoundary/launch.ps1) kezdő/záró SHA-256:
  `1d9c8040380974ddac2de1f90aa3d72aec00fb576c380a2a97eada1b1e420182`.
- Megőrzött [PoC README](../../tests/ProofOfConcept/RmaTransactionBoundary/README.md),
  [C3-02 report](rma-0004-c3-02-transaction-boundary-poc-results.md) és
  [BLOCKED evidence](../../tests/ProofOfConcept/RmaTransactionBoundary/evidence/results.json).
- Nem készült külön repositorybeli script/evidence fájl. Ez a jelentés az egyetlen új repository file.
- Nincs staging/commit/push/merge/rebase/branchváltás.

## 2. Original failure evidence

Helyi eredeti session JSONL:

`C:\Users\zolta\.codex\sessions\2026\10\27\rollout-2026-10-27T16-45-55-01a17e0a-1adc-73d2-bf63-74304aaad66b.jsonl`

- **2895. sor**, CommandExecution: 2026-11-05T15:21:17.706Z, budapesti idő16:21:17.
- **2896. sor**, tool-output: azonos kimenet.
- Mentett hiba: `A hozz�f�r�s megtagadva.`; a három U+FFFD már a tárolt rekordban van.
- Mentett **launcher exit1**; a korábbi icacls saját pontos exitje/belső Win32 API-kódja nincs rögzítve.
- `Owned sandbox cleanup: True`. Az akkori gyermekkönyvtár nincs meg.
- Történeti source: native command21., throw22. sor; jelenlegi source24–25. sor.
- Az új mérés exit5 értéke nem írható vissza a korábbi evidence-be.

A mostani próba eredeti CommandExecution rekordja ugyanebben a sessionben **3129. sor**,
2026-11-05T15:56:26.434Z. A most megőrzött raw bájtok a9. fejezetben szerepelnek.
A történeti token/DACL nem rekonstruálható ebből visszamenőleg.

## 3. Diagnostic environment

Inline PowerShell **7.6.9**, process PID **13680**.
A Win32 helper Add-Type segítségével memóriába fordult, nem keletkezett repositorybeli executable
vagy helper fájl. Szoftvertelepítés és Windows security beállítás módosítása nem történt.

Read-only API-k: GetDriveType, GetVolumeInformation, GetFinalPathNameByHandle,
GetFileInformationByHandle, OpenProcessToken/GetTokenInformation, IsTokenRestricted, AccessCheck.
A process primary tokenjének DuplicateToken(SecurityImpersonation) másolata szolgált az
AccessCheckhez; nem történt thread impersonation, tokenváltás vagy privilege-aktiválás.

Csak egy új könyvtár create-ja, egy icacls művelet arra, majd igazolt nem rekurzív delete történt.
MySQL-start/initialize, DB-kapcsolat, SQL és C3-02 PoC-run nem történt.

## 4. Parent directory assessment

Jóváhagyott teljes kanonikus szülő: `C:\Users\zolta\AppData\Local\Temp`.

| Gate                                    | Actual result                                                                                           |
| --------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| Helyi fixed drive                       | PASS, GetDriveType=3                                                                                    |
| Windows ACL támogatás                   | PASS, NTFS és FILE_PERSISTENT_ACLS                                                                      |
| UNC/network kizárása                    | PASS                                                                                                    |
| Reparse/symlink kizárása                | PASS, minden létező útvonalkomponens ellenőrizve                                                        |
| Kanonikus handle-path                   | PASS, GetFinalPathNameByHandle egyezik a tervezett abszolút path-tal                                    |
| Repository/application DB-root kizárása | PASS, nem a repository vagy helyi MySQL/MariaDB install-root alatt; friss gyermek alkalmazásadat nélkül |
| Szülő írhatósága                        | PASS, FILE_ADD_SUBDIRECTORY AccessCheck engedett; az actual egyszeri create is sikerült                 |
| Nincs korábbi cél újrahasználata        | PASS, random128-bit név + existence check + atomikus Win32 CreateDirectory                              |
| Szülő ACL megőrzése                     | PASS, külön utólagos read során az SDDL egyezik                                                         |

A szülő owner/group SID-je a process user SID. Protected=false, canonical=true.
Teljes Access/Owner/Group SDDL:

```text
O:S-1-5-21-175778158-3753295944-971442867-1001G:S-1-5-21-175778158-3753295944-971442867-1001D:AI(A;OICI;0x1301ff;;;S-1-5-21-2763601909-2140924296-1408363329-1201480788)(A;OICI;0x1301bf;;;S-1-5-21-175778158-3753295944-971442867-1005)(A;OICI;0x1301bf;;;S-1-5-21-993326613-3586677194-4165090672-941953849)(A;OICIID;0x1200a9;;;S-1-5-21-175778158-3753295944-971442867-1005)(A;OICIID;FA;;;SY)(A;OICIID;FA;;;BA)(A;OICIID;FA;;;S-1-5-21-175778158-3753295944-971442867-1001)
```

| SID                                                  | ACE type | Maszk és jog                                                 | Inherited |
| ---------------------------------------------------- | -------- | ------------------------------------------------------------ | --------- |
| S-1-5-21-2763601909-2140924296-1408363329-1201480788 | Allow    | 0x1301ff — DeleteSubdirectoriesAndFiles, Modify, Synchronize | false     |
| S-1-5-21-175778158-3753295944-971442867-1005         | Allow    | 0x1301bf — Modify, Synchronize                               | false     |
| S-1-5-21-993326613-3586677194-4165090672-941953849   | Allow    | 0x1301bf — Modify, Synchronize                               | false     |
| S-1-5-21-175778158-3753295944-971442867-1005         | Allow    | 0x1200a9 — ReadAndExecute, Synchronize                       | true      |
| S-1-5-18                                             | Allow    | 0x1f01ff — FullControl                                       | true      |
| S-1-5-32-544                                         | Allow    | 0x1f01ff — FullControl                                       | true      |
| S-1-5-21-175778158-3753295944-971442867-1001         | Allow    | 0x1f01ff — FullControl                                       | true      |

Minden ACE ObjectInherit+ContainerInherit, propagation=None. AddDirectory/DELETE a jelen tokennek
megadott, WRITE_DAC/FullControl nem. A szülő ACL-jét nem módosítottuk.
Az ACL-filesystem flag nem privacy proof.
Forrás: [GetVolumeInformationW](https://learn.microsoft.com/en-us/windows/win32/api/fileapi/nf-fileapi-getvolumeinformationw).

## 5. Process identity and token assessment

| Adat                           | Actual value                                                              |
| ------------------------------ | ------------------------------------------------------------------------- |
| Windows identity               | `DESKTOP-BGDBIM6\zolta`                                                   |
| User/default-owner SID         | `S-1-5-21-175778158-3753295944-971442867-1001`                            |
| Token type                     | 1 — primary                                                               |
| IsTokenRestricted              | **true**                                                                  |
| TokenElevationType raw value   | 2                                                                         |
| TokenElevation.TokenIsElevated | **false**                                                                 |
| Integrity SID                  | `S-1-16-12288` — High integrity RID; nem unrestricted/admin jogbizonyíték |
| Thread impersonation           | None                                                                      |
| Administrators S-1-5-32-544    | Attributes16: DENY_ONLY, nem enabled allow-jogforrás                      |

Restricting SID-k, mind attributes7:

- `S-1-5-21-993326613-3586677194-4165090672-941953849`
- `S-1-5-21-143526332-895940703-2941060043-1082655136`
- `S-1-5-5-0-78591732`
- `S-1-1-0`

A restricted-token ellenőrzésben az enabled és restricting SID-halmaz szerinti vizsgálatnak is
engednie kell a hozzáférést. A DACL-ben ténylegesen illeszkedő restricting SID
`S-1-5-21-993326613-3586677194-4165090672-941953849` csak Modify/Synchronize jogot kapott,
WRITE_DAC nélkül. Az owner/névleges FullControl ezért nem elegendő.
Forrás: [Restricted Tokens](https://learn.microsoft.com/en-us/windows/win32/secauthz/restricted-tokens).

A tokenmérés ugyanabban a processben történt, amely normál, nem emelt Process.Start hívással
indította az icacls-t. A gyermek tokenjét külön nem exportáltuk. A tokent előállító konkrét
sandbox/policy komponens e mérésből nincs azonosítva.

## 6. Test directory creation

**Pontosan egy** könyvtár jött létre; további create/retry nem történt.

- Teljes kanonikus út: `C:\Users\zolta\AppData\Local\Temp\RmaAclProbe-cb8038b133eba33e2d8a7274f910a858`.
- Név: RmaAclProbe + RandomNumberGenerator16 bájtos suffixe hex alakban.
- CreationTimeUtc: **2026-11-05T15:56:25.9965921Z**, budapesti idő16:56:25.9965921.
- Atomikus Win32 CreateDirectory: meglévő cél nem használható újra.
- Volume/file identity: **D08670DE / 002C000000158D4E**.
- Owner: `S-1-5-21-175778158-3753295944-971442867-1001`, egyezik a process user SID-vel.
- Üres könyvtár; credential, DB-adat és egyéb fájl nem került bele.
- Kezdő inheritance protection=false; canonical=true.

Kezdeti teljes Access/Owner/Group SDDL:

```text
O:S-1-5-21-175778158-3753295944-971442867-1001G:S-1-5-21-175778158-3753295944-971442867-1001D:AI(A;OICIID;0x1301bf;;;S-1-5-21-175778158-3753295944-971442867-1005)(A;OICIID;0x1301bf;;;S-1-5-21-993326613-3586677194-4165090672-941953849)(A;OICIID;0x1301ff;;;S-1-5-21-2763601909-2140924296-1408363329-1201480788)(A;OICIID;FA;;;SY)(A;OICIID;FA;;;BA)(A;OICIID;FA;;;S-1-5-21-175778158-3753295944-971442867-1001)
```

## 7. ACL operation

A C3-02-ével azonos argumentumok, csak az új célútvonalon:

```powershell
icacls.exe "C:\Users\zolta\AppData\Local\Temp\RmaAclProbe-cb8038b133eba33e2d8a7274f910a858" /inheritance:r /grant:r "DESKTOP-BGDBIM6\zolta:(OI)(CI)F"
```

Executable: `C:\WINDOWS\system32\icacls.exe`. ProcessStartInfo: UseShellExecute=false,
CreateNoWindow=true, ArgumentList, külön stdout/stderr redirect. Nem shellben összerakott
argumentumszöveg; a15 másodperces felső korlát nem telt le.

A két BaseStream külön MemoryStreambe került. A raw bájtok megőrződtek, a native output
nem került Out-Null-ba. Nem nulla process exit külön adatként rögzítve; emiatt nem dobtunk
olyan PowerShell-kivételt, amely eltakarná a process saját kódját.
A launcher korábbi throw-ját nem futtattuk.
Forrás: [icacls](https://learn.microsoft.com/en-us/windows-server/administration/windows-commands/icacls).

## 8. Exact exit codes

| Jelzés                               | Actual result                                                       |
| ------------------------------------ | ------------------------------------------------------------------- |
| Új icacls Process.ExitCode           | **5**, közvetlenül a child Process objektumból                      |
| Új native stderr OEM852 szerint      | **A hozzáférés megtagadva.**                                        |
| Új PowerShell-kivétel                | **Nincs**; Start/capture/WaitForExit sikerült                       |
| Inline diagnosztikai harness exit    | 0 — adatgyűjtés/cleanup siker, **nem ACL-success**                  |
| Korábbi C3-02 launcher exit          | 1                                                                   |
| Korábbi icacls saját exit            | UNKNOWN                                                             |
| icacls belső hibás Win32 API-kódja   | Külön nem mértük; nem azonosítjuk automatikusan a process exit5-tel |
| AccessCheck végrehajtás WRITE_DAC-ra | API sikerült; AccessStatus=false, GrantedAccess=0                   |

Az AccessCheck wrapper win32Error=0-ja sikeres API-hívást jelent, nem a jog megadását.
Külön kezeljük az API-return és AccessStatus eredményt.
Forrás: [AccessCheck](https://learn.microsoft.com/en-us/windows/win32/api/securitybaseapi/nf-securitybaseapi-accesscheck).

## 9. ACL before/after comparison and raw evidence

| Jellemző                   | Before             | After                           |
| -------------------------- | ------------------ | ------------------------------- |
| Owner                      | User SID           | Ugyanaz                         |
| Teljes SDDL                | Rögzített snapshot | **Pontos karakterlánc-egyezés** |
| Protected inheritance      | false              | false                           |
| Inherited allow ACE        | 6                  | 6                               |
| Explicit allow ACE         | 0                  | 0                               |
| Explicit deny ACE          | 0                  | 0                               |
| Canonical DACL             | true               | true                            |
| Current token AddDirectory | ALLOWED            | ALLOWED                         |
| Current token WRITE_DAC    | DENIED             | DENIED                          |
| Current token DELETE       | ALLOWED            | ALLOWED                         |
| Current token FullControl  | DENIED             | DENIED                          |

Az after SDDL azonos a6. fejezet kezdeti SDDL-jével. Nincs részleges inheritance/grant módosítás.
A SYSTEM és Administrators FullControl önmagában nem security failure.
A user/SYSTEM/Administrators allowlistán túl három SID örökölt Modify-szerű joggal szerepel:
a user-domain1005 végű, a993326613 kezdetű restricting, illetve a2763601909 kezdetű SID.
Ettől a kívánt explicit owner-only protected DACL nem állt elő. Nem állítjuk, hogy mindhárom
SID támadó vagy hogy egy másik tokennel a tényleges hozzáférésüket bizonyítottuk.

**stdout, 59 bájt:**

```text
Successfully processed 0 files; Failed processing 1 files
```

**stderr, 106 bájt:**

```text
C:\Users\zolta\AppData\Local\Temp\RmaAclProbe-cb8038b133eba33e2d8a7274f910a858: A hozzáférés megtagadva.
```

GetOEMCP=852, GetACP=1250, GetConsoleOutputCP=65001. Ugyanezek a stderr-bájtok UTF-8-ként
olvasva a korábbi `A hozz�f�r�s megtagadva.` alakot adják. A mostani dekódolási hibát
ez megmagyarázza; az eredeti elveszett raw bájtok ettől még nem állnak rendelkezésre.

A raw bájtok base64-ben és a minimális eredményhalmaz, külön evidence fájl nélkül:

```json
{
    "probe_started_utc": "2026-11-05T15:56:25.9565673Z",
    "created_utc": "2026-11-05T15:56:25.9965921Z",
    "path": "C:\\Users\\zolta\\AppData\\Local\\Temp\\RmaAclProbe-cb8038b133eba33e2d8a7274f910a858",
    "file_identity": {
        "volume": "D08670DE",
        "fileId": "002C000000158D4E"
    },
    "process_pid": 13680,
    "icacls_exit_code": 5,
    "stdout_byte_length": 59,
    "stderr_byte_length": 106,
    "stdout_raw_base64": "U3VjY2Vzc2Z1bGx5IHByb2Nlc3NlZCAwIGZpbGVzOyBGYWlsZWQgcHJvY2Vzc2luZyAxIGZpbGVzDQo=",
    "stderr_raw_base64": "QzpcVXNlcnNcem9sdGFcQXBwRGF0YVxMb2NhbFxUZW1wXFJtYUFjbFByb2JlLWNiODAzOGIxMzNlYmEzM2UyZDhhNzI3NGY5MTBhODU4OiBBIGhvenqgZoJygnMgbWVndGFnYWR2YS4NCg==",
    "oem_codepage": 852,
    "ansi_codepage": 1250,
    "console_output_codepage": 65001,
    "powershell_exception": null,
    "before_write_dac": {
        "apiSucceeded": true,
        "allowed": false,
        "desired": 262144,
        "granted": 0,
        "win32Error": 0
    },
    "after_write_dac": {
        "apiSucceeded": true,
        "allowed": false,
        "desired": 262144,
        "granted": 0,
        "win32Error": 0
    },
    "sddl_unchanged": true,
    "parent_sddl_unchanged": true,
    "cleanup": {
        "attempted": true,
        "removed": true,
        "verifiedSameCanonicalPathAndFileId": true
    },
    "probe_absent": true,
    "verdict": "ACL FAILED"
}
```

## 10. Isolation verification

| Ellenőrzés                                              | Verdict                                                                |
| ------------------------------------------------------- | ---------------------------------------------------------------------- |
| Lokális ACL-fájlrendszer, nem reparse/repository/DB cél | PASS                                                                   |
| Friss, ismert ownerű, saját könyvtár                    | PASS                                                                   |
| ACL command siker                                       | **FAIL, exit5**                                                        |
| DACL configuration correctness                          | **FAIL**, inheritance nem protected, explicit owner-only grant nincs   |
| Explicit deny                                           | Nincs; az effektív denial nem explicit deny ACE-ből következik         |
| Unintended inherited grants                             | Három allowlistán kívüli SID jelen van                                 |
| Current token create/delete access                      | PASS, AccessCheck és actual műveletek                                  |
| Current token WRITE_DAC / FullControl                   | **DENIED**                                                             |
| Más principal actual token-access próbája               | **UNVERIFIED**, idegen token/credential/account nem került használatba |
| Safe cleanup                                            | PASS                                                                   |
| Teljes private isolation                                | Nem igazolt, a DACL-forma is hibás: **ACL FAILED**                     |

AccessCheck a process primary tokenjének impersonation másolatán, az olvasott Access/Owner/Group
security descriptorral és specific access maskokkal futott; nincs unmapped GENERIC bit.
Ez nem teljes SACL/central policy/MIC/object-manager audit. A tényleges native denial és a sikeres
create/delete további megfigyelés. Más principal tényleges effektív jogát nem állítjuk igazoltnak.

## 11. Cleanup result

**PASS. A könyvtár törlődött, maradvány nincs.**

Közvetlenül a törlés előtt újra ellenőrizve: minden komponens nem reparse; handle-kanonikus path
egyezik; pontos approved parent/random basename; változatlan volume/file ID; owner egyezik a
process userrel; effektív DELETE megadva; könyvtár üres.

Ezután kizárólag Directory.Delete(path, recursive=false) futott erre a gyermekre.
A létezésellenőrzés false-t adott, külön utóellenőrzés is megerősítette.
A szülő utólag olvasott SDDL-je egyezik a kezdetivel. Nincs rekurzív delete vagy ACL-korrekció.

## 12. Root-cause assessment

**Bizonyított jelenlegi közvetlen ok:** a process létrehozhatja/törölheti a gyermeket, de nincs
effektív WRITE_DAC-ja; az AccessCheck előtte és utána is DENIED. Az icacls exit5/Access denied
eredmény ezzel egyezik.

**Erősen alátámasztott mechanizmus:** restricted primary token, Administrators DENY_ONLY SID,
a matching restricting SID-nél Modify grant WRITE_DAC nélkül. A névleges user-owner/FullControl
nem elég a restricted végrehajtásnak.

**Nem bizonyított:** mely sandbox/policy komponens hozta létre a tokent; az icacls belső hibás
Win32 API-hívása; a történeti C3-02 folyamat teljes token/DACL állapota. Az új próba reprodukálja
a hibatípust, nem pótolja visszamenőleg a hiányzó történeti adatot.

A network/reparse/ACL-filesystem hiány nem magyarázza a jelen futást: e kapuk PASS-oltak.
Nem névfeloldási hibaüzenetet kaptunk. Alternatív SID-szintaxissal vagy más tokennel nem
próbálkoztunk: csak egy azonos ACL-művelet és egy új könyvtár volt engedélyezett.

## 13. Recommended remediation

1. A jelen kontextusban ne folytassuk automatikusan a PoC-t; a create jog nem isolation PASS.
2. Projektgazdai/operator review válasszon dedikált jóváhagyott környezetet, ahol a private DACL
   kialakítása és a tényleges runtime token szükséges effektív joga egyaránt igazolható.
3. A később külön engedélyezett próbában a raw output/exit megőrzését tartsuk meg. Az owner-only
   contractnak a restricted runtime mellett is működnie kell; ha ez a policyval nem egyeztethető össze,
   a security/environment szerződést kell review-zni. Nem automatikus extra SID-grant vagy gate-kikapcsolás.
4. SYSTEM/Administrators működési szükséglete és az unintended principalok kizárása külön
   threat-model/effective-access ellenőrzést igényel.
5. Nem módosítottunk launchert, parent/system ACL-t, sandbox security beállítást vagy régi evidence-et;
   automatikus elevation-kérés nem történt.

## 14. Remaining uncertainties and validation

Más principal actual access-e, SACL/central policy és a token létrehozója UNVERIFIED.
A régi raw output/exit nem rekonstruálható bizonyítékként a mostani mérésből.
A helper nem telepített/production diagnosztikai eszköz; csak az engedélyezett inline vizsgálat része.

Dokumentációs DoD: [Definition of Done](../../docs/project-management/definition-of-done.md).
Célzott Prettier, relatív linkek, raw base64/byte-length/OEM roundtrip, whitespace,
`git diff --check`, a932 korábbi fájl hash-egyezése és a launcher külön hash-e ellenőrzött.
A task által kért teljes temp/canonical path és releváns security SID-k szándékosan szerepelnek.
Credential, token-secret vagy unrelated user adat nincs. App/PHP/SQL/migration teszt N/A.

## 15. Final verdict

**ACL FAILED.** A filesystem-létrehozási gate sikerült, de icacls exit5 és effektív WRITE_DAC denial
mellett a protected private DACL nem állt elő. A diagnosztikai könyvtár biztonságosan törlődött.
A régi C3-02 POC BLOCKED állapota változatlan. Nincs MySQL/SQL/PoC, system/parent ACL-módosítás
vagy Git-mentés. Következő lépés projektgazdai review.
