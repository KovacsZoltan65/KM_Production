# RMA-0004 / C3-02 — Isolated Transaction Boundary PoC Results

Verdikt: **POC BLOCKED**, 2026-11-05. Az izolációs kapu sikertelen;
a prototípus és T01–T14 **NOT EXECUTED**. Nem implementation-ready vagy migration-ready.

## 1. Baseline and Git state

- Branch: `feature/returns-repair-management`.
- HEAD: `e21e83da44e7ec2228efa6c5c6f8d1021340b73b`, változatlan.
- Kezdés: üres tracked diff/index; nyolc untracked C2/C3 dokumentum.
- A kezdő922 Git-inventory fájl rendezett `(relatív útvonal,SHA-256)` JSON-listájának digestje:
  `afcd796d4ce68ba389949ae0e3984990080f3d0a4585d44b16d36932a290d1db`.
- Új scope: hét PoC-fájl a dedikált könyvtárban és ez a jelentés. Meglévő fájl nem változott.
- Végső Git-inventory: 8 → 16 untracked fájl. A SQL-definíció `schema.mysql` néven szerepel,
  mert a meglévő `.gitignore` a `*.sql` fájlokat kizárja; ignore/config módosítás nem történt.
- Nincs staging/commit/push/merge/rebase/branchváltás; tracked diff és index záráskor is üres.

## 2. Environment isolation proof — BLOCKED

Egy [launcher](../../tests/ProofOfConcept/RmaTransactionBoundary/launch.ps1)-kísérlet történt.
Friss, run-owned temporary könyvtár létrejött. A kötelező, aktuális Windows-identityre korlátozott
privát ACL beállításakor az `icacls` **Access denied / A hozzáférés megtagadva** hibát adott.
A launcher ellenőrizte a nem nulla exit kódot, és megállt:
`Private sandbox ACL could not be established`. A shell exit kódja1.

A C3-02 feladat4. pontja előírja: **“If isolation cannot be proven, STOP.”** Ezt alkalmaztuk.
Nem hagytuk ki a kaput, nem lazítottuk a követelményt, nem próbáltunk alkalmazáscredentiallel
vagy másik adatbázison továbbhaladni. Ez Windows-hozzáférési hiba volt, nem automatikus
approval-review elutasítás és nem SQL-/tranzakcióteszt-hiba.

| Gate / művelet                                           | Actual result                                                           |
| -------------------------------------------------------- | ----------------------------------------------------------------------- |
| Branch/HEAD/inventory                                    | PASS                                                                    |
| MySQL8.4 executable azonosítás                           | PASS, kizárólag `--no-defaults --version`                               |
| Friss run-owned temporary könyvtár                       | Létrejött                                                               |
| Kötelező privát ACL                                      | **FAIL — OS access denied**                                             |
| Tesztcredential-generálás / bootstrap file               | NOT REACHED; a forrásban az ACL után következik                         |
| MySQL initialize / server start                          | **NOT EXECUTED**                                                        |
| Saját listener/PID/datadir, DB/grant/schema provisioning | BLOCKED / NOT EXECUTED                                                  |
| DB-kapcsolat / SQL / T01–T14                             | **NOT EXECUTED**                                                        |
| Cleanup                                                  | PASS: a run-owned temporary könyvtár törlődött; server child nem indult |

Hiányzó előfeltétel: ACL-képes jóváhagyott izolált környezet, vagy külön review-zott eldobható
MySQL-környezet explicit allowlisttel és elkülönített test-only principalokkal. Az utóbbihoz a
fresh-native-server harness külön adaptációja szükséges; meglévő alkalmazásszerver nem adható meg
fallbackként. Éles hitelesítőadatokra nincs szükség. Új DB-kísérlet nem indul automatikusan.

## 3. MySQL / PHP / Laravel / PDO versions

| Elem                         | Bizonyítékhatár                                                                                                         |
| ---------------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| MySQL executable             | Community Server **8.4.7 Win64**, `mysqld --no-defaults --version`; futó DB-verzió nincs                                |
| PHP                          | Helyi **8.4.27** CLI a syntax/Pint ellenőrzéshez; nem app- vagy DB-runtime bizonyíték                                   |
| Laravel                      | Lock/vendor metadata **v13.38.0**, source `c42f9abd4a3d2f9a3cd6be109255ddce8092ceb8`; nincs live integration result     |
| PDO                          | Explicit native prepare/nonpersistent connection terve; tényleges client/connection/transaction metadata **UNVERIFIED** |
| Engine/schema/charset/grants | **UNVERIFIED**, csak előkészített InnoDB schema source létezik                                                          |

## 4. Transaction model implementation — prepared source only

Elsődleges terv: [C3-01](rma-0004-c3-01-transaction-integrity-proof-plan.md).
Megőrzött autoritás: [Accepted ADR-0006](../decisions/0006-rma-repair-management-architecture.md),
[ADR-0001](../decisions/0001-stock-movements.md),
[C1](rma-0003c1-business-decisions-integrity-gates.md),
[WP2](rma-0003c2-wp2-mysql-schema-integrity-review.md),
[WP3](rma-0003c2-wp3-inventory-mrp-consumer-contracts.md),
[WP7](rma-0003c2-wp7-verification-migration-readiness.md).
RMA-BD-001–020, D1–D11, F1–F4, AM-01–04 és AM-02-G1–G4 jelentése változatlan;
a PoC ezek egyikének implementációját vagy teljesülését sem állítja.

Források: [Prototype.php](../../tests/ProofOfConcept/RmaTransactionBoundary/Prototype.php),
[schema.mysql](../../tests/ProofOfConcept/RmaTransactionBoundary/schema.mysql),
[run.php](../../tests/ProofOfConcept/RmaTransactionBoundary/run.php),
[worker.php](../../tests/ProofOfConcept/RmaTransactionBoundary/worker.php).

A prototípus Model C standalone Laravel database componentet használ alkalmazásbootstrap nélkül.
Dedikált fresh connection/root, explicit caller-connection registry tiltja a nestinget;
service/executor commitol és rollbackel; whole-command retry legfeljebb két kísérlet1205/1213-ra.
Ez nem production retry-policy vagy globális caller-discovery bizonyítéka.

**C3-01 technikai finomítása:** a legújabb C3-02 tiltja a routine caller-root rollbackjét.
Ezért a routine nem kap COMMIT/ROLLBACK/START TRANSACTION utasítást. Egyetlen command INSERT
triggereli a business+immutable evidence írást ugyanazon InnoDB-statementben; hiba esetén
statement undo az elvárás, nem unrelated caller-work rollback. Nem történik Model B-re váltás.

A [MySQL trigger dokumentáció](https://dev.mysql.com/doc/refman/8.4/en/trigger-syntax.html)
általános mechanizmusa indokolja az irányt; a konkrét séma/routine/grant/executor **nem igazolt**.
Nem általános megoldás a teljes többállításos RMA-finalizerre. Autocommit nem ownership proof.

## 5. Principal and privilege matrix — NOT PROVISIONED

| Tervezett principal | Prepared source contract                                                                                                         | Actual proof                                          |
| ------------------- | -------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------- |
| Setup/admin         | Csak friss külön serverben schema/user/grant/observer/shutdown; generált tesztsecret                                             | BLOCKED, nem generálódott secret, nem nyílt kapcsolat |
| Runtime             | Protected három tábla SELECT, complete routine EXECUTE, synthetic caller-sentinel INSERT; protected DML/DDL/helper EXECUTE nincs | BLOCKED, account/grant nem jött létre                 |
| Definer             | Locked login; scoped SELECT/INSERT/UPDATE/TRIGGER és szükséges routine EXECUTE                                                   | BLOCKED, effective rights nem tesztelt                |
| Independent worker  | Csak runtime config; admin/definer secret nélkül                                                                                 | BLOCKED, worker nem indult                            |

Egy synthetic actor/login páros SESSION_USER bindingje készül, nem caller parameter/sessionflag
authority. Ez nem shared-principal HTTP actor-hitelesítési vagy deployment secret-isolation proof.

## 6. Test results T01–T14

Az [evidence manifest](../../tests/ProofOfConcept/RmaTransactionBoundary/evidence/results.json)
minden esethez megadja az előírt11 mezőt: precondition, principal, connection identity, transaction
state, operation, expected, actual, DB evidence, Laravel/PDO evidence, cleanup és WP7 mapping.

| ID  | Előkészített eset                               | Actual result          | WP7             |
| --- | ----------------------------------------------- | ---------------------- | --------------- |
| T01 | Root success / precommit observer               | BLOCKED / NOT EXECUTED | V38/V42/V43     |
| T02 | Root rollback / business fault                  | BLOCKED / NOT EXECUTED | V38/V43         |
| T03 | Nested entry deny / caller savepoint megőrzés   | BLOCKED / NOT EXECUTED | V42/V43         |
| T04 | Exception / raw caught error+COMMIT / no-BEGIN  | BLOCKED / NOT EXECUTED | V38/V42/V43     |
| T05 | Protected DML/DDL/session bypass deny           | BLOCKED / NOT EXECUTED | V31/V33/V41/V43 |
| T06 | Authorized complete routine / helper deny       | BLOCKED / NOT EXECUTED | V41/V43         |
| T07 | Identity replay / changed payload conflict      | BLOCKED / NOT EXECUTED | V05/V06/V43     |
| T08 | Két process/session / actual duplicate-key wait | BLOCKED / NOT EXECUTED | V06/V40/V42/V43 |
| T09 | 1205 lock conflict / fresh-root retry           | BLOCKED / NOT EXECUTED | V38/V40/V42/V43 |
| T10 | Simulated lost acknowledgement / lookup         | BLOCKED / NOT EXECUTED | V06/V40/V43     |
| T11 | Evidence atomicity / immutable guard            | BLOCKED / NOT EXECUTED | V33/V38/V43     |
| T12 | Failed lease discard / next fresh connection    | BLOCKED / NOT EXECUTED | V38/V42/V43     |
| T13 | Login-bound actor / invalid hash deny           | BLOCKED / NOT EXECUTED | V14/V41/V43     |
| T14 | Registered service-style caller boundary        | BLOCKED / NOT EXECUTED | V42/V43/V48     |

**0 PASS, 0 executed test FAIL, 0 PARTIAL, 14 BLOCKED**. Külön a környezet ACL kapuja **FAIL**.
Ez nem14 runtime test-failure, és az előkészített source tervezett PARTIAL kimenete sem actual result.

## 7. Failed and blocked scenarios

Egy native launcher-kísérlet; ACL exception és shell exit1; verified cleanup; no retry/fallback.
A [README](../../tests/ProofOfConcept/RmaTransactionBoundary/README.md) tiltja a kapukihagyást és
az automatikus rerunt. A blocked evidence később sem írható át történet nélkül.

## 8. Actual evidence and static validation

- [results.json](../../tests/ProofOfConcept/RmaTransactionBoundary/evidence/results.json): megfigyelt
  launcher failure,14 BLOCKED eset, scope/baseline és forráshash-ek. **Nem a DB runner generálta**:
  az izolációs hiba miatt az nem indult el. A manifest ezt explicit jelöli.
- Három PHP-fájl syntax és célzott Pint ellenőrzése. Az első syntax kör nullable-deprecationt
  jelzett; a formatter explicit nullable típusra javította, bármely DB-futtatás előtt.
- PowerShell AST parse: szintaxis elfogadott, runtime ACL fail ettől független.
- Célzott Markdown/JSON, relatív link, whitespace és sensitive-pattern ellenőrzések.
- `git diff --check`, üres tracked/index és kezdő922 fájl hash-egyezés.
- Full app suite/PHPStan/SQL/integration/migration gate nem futott; a safety STOP után a
  statikus check nem helyettesítheti a hiányzó runtime/security bizonyítékot.

## 9. WP7-F01 assessment

**OPEN / BLOCKED.** Konkrét minimal statement-atomicity irány készült, de ownership, raw CALL,
nested/cross-connection refusal, principal/actor, rollback/retry és driver-state proof nincs.
Scoped closure sem állítható. WP2-F02 és C1-G1/G2/G3 kapuk változatlanok.

## 10. WP7-F02 assessment

**OPEN / BLOCKED.** Harness/fixture source készült, de környezetprovisioning nem jutott át az
izolációs kapun. Nincs tesztadatbázis/account vagy actual result a14 teszthez. Ez megfigyelt
környezeti előfeltételhiány, nem production defect vagy runtime transaction failure.

## 11. Remaining risks

- A prepared schema/runner SQL, grant és driver működése teljesen UNVERIFIED; további hibák is lehetnek.
- T10 csak sikeres commit utáni ack-vesztést szimulálna; real ambiguous COMMIT/network fault nincs.
- T14 standalone Laravel caller-minta, nem existing inventory/production/procurement service-integráció.
- Egy actor/login nem shared runtime actor-binding vagy deployment credential-boundary megoldás.
- Egy statement/trigger nem teljes25-táblás RMA-finalizer/lifecycle/mandatory-proof totality.
- Genuine1213, mode0, afterCommit/cache, pool/worker, deployment/legacy/recovery és canonical48 teljes
  bizonyítéka nincs meg. Az accepted üzleti döntések nem változtak.

## 12. Final readiness verdict

**POC BLOCKED.** Nem indult DB, nem futott SQL vagy T01–T14 teszt. A forrás és dokumentáció a
hiányzó környezeti előfeltétel és a technikai irány review-jára adható át; funkcionális készültség nincs.

Nincs application/config/route/policy/existing migration/business-table módosítás. A korábbi
dokumentumok és B eredmények változatlanok. Nincs staging/commit/push/merge/rebase/branchváltás.
A teljes RMA implementáció és production migráció továbbra sem engedélyezett. A STOP után
további adatbázis-kísérlet nem indul; projektgazdai review és igazolt izolált környezet szükséges.
