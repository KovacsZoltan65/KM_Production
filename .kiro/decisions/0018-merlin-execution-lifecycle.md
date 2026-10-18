# Merlin Execution Lifecycle V1

- **Állapot:** ACCEPTED az alább körülhatárolt architekturális irányra; az OPEN részletek nem elfogadott implementációs szerződések, és ez nem implementációs engedély.
- **Dátum:** 2026-10-18.
- **Feladat:** `MERLIN_EXECUTION_ADR_0018_DESIGN`.
- **Korábbi célzott felülvizsgálat:** `MERLIN_ADR_0018_TARGETED_REVIEW`, 2026-10-18; ekkor a user az ELV1-03 szerinti uncertainty/resolution/invalidation osztályozási elvet fogadta el.
- **Architekturális irány elfogadása:** `MERLIN_ADR_0018_ACCEPT_AND_COMMIT`, 2026-10-18; kizárólag az 1. részben rögzített hatókörrel, az OPEN döntések és kötelező implementációs kapuk fenntartásával.
- **Ellenőrzött baseline:** `feature/merlin-candidate`, `0b12ad0f903bffc3255043a5f073c0cb0b1c20a7`; azonos local és tracking HEAD, tiszta worktree, üres staging.
- **Előzmény:** a beszélgetésben rendelkezésre álló `MERLIN_RUN_EXECUTION_LIFECYCLE_V1_READINESS` audit; állításai az alábbi repository-forrásokkal összevetve.
- **Kapcsolat:** [ADR 0017](0017-merlin-investigation-foundation.md), [Investigation Foundation V1](../../docs/specifications/merlin/investigation-foundation-v1.md), [ADR 0005](0005-document-ai.md).

## 1. Kontextus és probléma

A rendszer már biztonságosan befogad egy vizsgálatot, de annak futását még nem
irányítja. A következő terv azt rögzíti, hogyan kaphat egyetlen worker időben
korlátozott futási jogot, és hogyan zárható le a vizsgálat ismételt üzenet,
folyamatleállás vagy jogosultságváltozás esetén is követhető módon.

KM_Production irányítja Merlin futását. Merlin tanácsot ad. Az ember dönt.
Üzleti műveletet külön jogosított KM_Production workflow hajt végre.
A Laravel worker technikai folyamat; nem az AI-nak adott adatbázis-hozzáférés.

**ACCEPTED előzmény:** ADR 0017 öt állapota, transition topologyja, friss worker-entry
és tool-boundary authorizationje, a 8/4/180 limitek, az egy aktív run/case szabály,
az advisory határ és a terminális lezárástól számított 365 napos retention.
Ezeket ez az ADR megőrzi.

**ACCEPTED user elv:** megbízhatóan nem ellenőrizhető eligibility/authoritative
state -> FAILED; bizonyított resolution vagy bizonyítottan érvénytelen/már nem
alkalmazható investigation basis -> CANCELLED. Ez nem business state update.
Az elv pontos hatókörét az ELV1-03 rögzíti.

**ACCEPTED architekturális irány:** runonként egy sikeres worker claim;
backend-generált claim token és fencing; immutable, legfeljebb 180 másodperces
execution deadline; nincs automatikus resume vagy worker takeover V1-ben;
lifecycle service által birtokolt guarded transitionök; kötelező atomikus
execution audit; friss authorization és eligibility check; tartós model-turn
és tool-attempt budget; uncertain commit reconciliation; paid inference tiltása.

**Az elfogadás határa:** az ELV1-01–ELV1-10 fejezetek e körbe eső iránya
elfogadott. A pontos transition/command contract, reason code-ok és további
mapping policyk, mezők/osztálynevek, persistence schema/bounds, clock/deadline
representation, command authority és execution event/evidence contract
**PROPOSED/OPEN** marad. Az elfogadás nem zárja le az operatív, delivery,
connection-loss vagy provider/cost döntéseket, és nem indít L1 implementációt.
Az egyes fejezetek technikai javaslatai az elfogadott irányt részletezik;
önmagukban nem válnak elfogadott implementációs contracttá.
**OPEN:** ahol a szerződéshez további döntés vagy környezeti bizonyíték kell,
azt a 14. rész külön felsorolja. A kapuk kötelezőek; nyitott részletet az ADR
elfogadása nem alakít kész implementációs szerződéssé. Ez a fájl nem implementál
és nem engedélyez futást.

## 2. Ellenőrzött jelenlegi implementáció

A meglévő kód az indulást védi; a futásra vonatkozó követelmény nem azonos egy
már működő runtime kontrollal. Az alábbi hivatkozások a fenti commit forrásaira
vonatkoznak, nem új tesztfutások eredményei.

| Forrás és hely                                                                                                                                                               | Tényleges viselkedés                                                                                                                                      | Hiányzó lifecycle képesség                                                 |
| ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------- |
| [MerlinRun migration](../../database/migrations/2026_10_13_000001_create_merlin_run_foundation.php), 33–119. sor                                                             | Öt status, soron belüli timestamp/JSON/size CHECK, generated active marker, active/idempotency unique, same-case history FK, külön admission audit        | Claim, deadline, runtime counter és execution event nincs                  |
| [MerlinRun](../../app/Models/MerlinRun.php), 37–60. sor; [status enum](../../app/Enums/MerlinRunStatus.php), 5–16. sor                                                       | Typed state és kapcsolatok; Eloquent admission identity/config és terminal update guard                                                                   | Nincs adatbázisban ellenőrzött transition topology vagy worker fencing     |
| [Start service](../../app/Services/Merlin/StartMerlinInvestigationService.php), 40–123. sor                                                                                  | Auth guard actor, fresh permission, replay, active slot, authoritative eligibility; közös history/run/audit commit                                        | Nem dispatchol jobot, nem hajt végre vizsgálatot                           |
| [Admission transaction](../../app/Support/Merlin/MerlinAdmissionTransaction.php), 22–98. sor                                                                                 | Saját connection/PDO; caller transaction tiltás; MySQL REPEATABLE READ/READ WRITE, SQLite file-backed IMMEDIATE; case-lock-first; nincs automatikus retry | Nem worker ownership vagy lease manager                                    |
| [Run repository](../../app/Repositories/Admin/MerlinRunRepository.php), 17–60. sor; [interface](../../app/Repositories/Contracts/MerlinRunRepositoryInterface.php)           | Identity és active lookup, starting history append, PENDING create                                                                                        | Nincs claim, cancellation, finalize vagy recovery művelet                  |
| [Admission audit writer](../../app/Services/Merlin/MerlinAdmissionAuditWriter.php), 13–22. sor                                                                               | Szinkron, sanitizált admission event ugyanazon connectionön                                                                                               | Egy audit/run; nem execution journal                                       |
| [Collision classifier](../../app/Support/Merlin/MerlinAdmissionUniqueCollision.php), 10–27. sor; start service, 48–57. sor                                                   | Csak két ismert unique constraint után friss autorizált reconciliation                                                                                    | Nem általános retry vagy exactly-once execution                            |
| [AppServiceProvider](../../app/Providers/AppServiceProvider.php), 115–116., 184–186. sor                                                                                     | Repository/audit interface binding és meglévő super-admin Gate-konvenció                                                                                  | Nincs lifecycle service/worker binding                                     |
| [Tool](../../app/Services/Merlin/GetSupplierOptionsTool.php), 49–143. sor; [read composition](../../app/Services/Merlin/MaterialShortageSupplierOptionsRead.php), 49–74. sor | Külön availability/execution authorization, allowlist, bound case és fresh domain gate; caller transaction elutasítás                                     | Nincs run/call ownership, counter vagy deadline enforcement                |
| [Configuration](../../app/Support/Merlin/MerlinRunConfiguration.php), 10–25. sor                                                                                             | Snapshot: 8 model turn, 4 tool attempt, 180 másodperc; `NOT_CONFIGURED` cost, paid false                                                                  | Nincs ezeket fogyasztó runtime dispatcher                                  |
| [Queue config](../../config/queue.php), 16., 38–74. sor; [console routes](../../routes/console.php)                                                                          | Database default, database/Redis `retry_after=90`, `after_commit=false`; nincs Merlin recovery schedule                                                   | Deployment, queue visibility és worker termination policy nincs bizonyítva |

A [persistence](../../tests/Feature/MerlinRunPersistenceTest.php),
[migration](../../tests/Feature/MerlinRunMigrationTest.php),
[admission infrastructure](../../tests/Feature/MerlinAdmissionInfrastructureTest.php),
[admission service](../../tests/Feature/MerlinAdmissionServiceTest.php),
[contract](../../tests/Unit/MerlinAdmissionContractTest.php) és
[collision](../../tests/Unit/MerlinAdmissionCollisionTest.php) tesztek meglévő
fedezete admissionre és storage invariánsokra vonatkozik. Worker lifecycle
bizonyítékot nem helyettesítenek. E dokumentációs feladatban teszt nem futott.

A jelenlegi specification/ADR 0017 egyes commit-függőben mondatai történeti
státuszt őriznek: a vizsgált HEAD már tartalmazza az admission implementációt.
Ez nem igazol review-, merge- vagy production-readiness-t. A forrásdokumentumok
ebben a feladatban változatlanok maradnak.

## 3. Döntési szempontok

Egyszerű, bizonyítható V1 kell: egy worker indulhat, a régi worker nem írhatja
felül az újabb lezárást, és hiba esetén nem ismétlődhet láthatatlanul munka.

- Laravel-owned lifecycle, `Service -> Repository -> Model` rétegzés.
- Adatbázisban érvényesített ownership és idempotencia; queue nem exactly-once garancia.
- Rövid tranzakciók, nincs model/provider/tool hívás nyitott lifecycle tranzakcióban.
- Friss user permission, backend AI allowlist és case context metszete.
- Tartós, minimális audit; tanács és business execution elkülönítése.
- MySQL és SQLite kompatibilitás, külön bizonyított konkurenciaszemantika.
- Nincs új paid execution, generic registry vagy automatikus execution resume.

## 4. Elfogadott architekturális irány és nyitott technikai részletek

### ELV1-01 — Worker ownership: egyszeri claim és fix határidő

Egy run futását legfeljebb egy sikeres worker claim indíthatja el. Leállt worker
helyett V1-ben másik worker nem folytatja ugyanazt a vizsgálatot.

**Alternatívák:** fix lease; heartbeat által megújított lease és takeover;
kizárólag queue/cache lock. **Javaslat:** fix lease, mert a futás eleve legfeljebb
180 másodperc, és nincs elfogadott resume protokoll. A queue/cache lock csak
opcionális terheléscsökkentés lehet, nem írási authority.

A backend claimkor generál `claim_id` és titkos `claim_token` értéket; tartósan
csak a token ellenőrzéséhez szükséges hash tárolandó. A claim előfeltétele
`status=pending AND claim_id IS NULL`. Sikeres tranzakcióban egyszer állítja be
a `started_at` és `execution_deadline_at=started_at+180 seconds` értékeket,
az ownershipot és a claim eventet. Ezek utólag nem hosszabbíthatók meg.
A 180 másodperc actual starttól számít, a PENDING queue wait nem fogyasztja.

**PROPOSED pontosítás:** ha a később elfogadott pending policy szerint
`now >= pending_deadline_at`, claim már nem engedhető, akkor sem, ha a scanner
még nem zárta le a runt. A konkrét időérték és régi run policy ELV1-07-ben OPEN;
e pontosítás nem állít be határidőt.

Nincs heartbeat, takeover, RUNNING újraclaim vagy automatikus resume. A worker
crash után a run a tartós ownership lejártáig RUNNING maradhat; a recovery a
lejárt, nem megújítható lease bizonyítéka alapján FAILED-ra zárja. Ez nem
pusztán a run kora alapján történő cleanup. Új vizsgálat új admission/run.
Lejárt vagy lezárt ownershipból érkező válasz nem írhat resultot és nem
dispatcholhat új toolt. Titkos token nem kerül queue payloadba, advice-ba vagy logba.

### ELV1-02 — A transition tulajdonosa a lifecycle service

A run állapotát egy belső Laravel service változtathatja, ellenőrzött repository
műveleteken keresztül. Egy elavult model példány nem ad futási jogot.

**Alternatívák:** közvetlen Eloquent save; hosszú tranzakció a teljes futás körül;
rövid tranzakció és conditional update. **Javaslat:** utóbbi, egy tervezett
`MerlinRunLifecycleService` határral; ez még nem létező osztály.
Az admission PENDING-létrehozása változatlanul a meglévő start service feladata;
a már létrehozott run transitionjeit kizárólag a lifecycle service birtokolja.

| Forrás                       | Engedett cél | Feltétel                                                                    |
| ---------------------------- | ------------ | --------------------------------------------------------------------------- |
| PENDING                      | RUNNING      | Egyszeri, fresh-authorized és eligible claim                                |
| PENDING                      | FAILED       | Kontrollált indulási hiba vagy definiált pending deadline recovery          |
| PENDING                      | CANCELLED    | Jogosult cancellation vagy elfogadott runtime megszűnési ok                 |
| RUNNING                      | COMPLETED    | Érvényes owner/deadline, friss authorization/eligibility, validált eredmény |
| RUNNING                      | FAILED       | Owned failure vagy ellenőrzött backend recovery                             |
| RUNNING                      | CANCELLED    | Jogosult cancellation vagy backend által észlelt jogosultság/case változás  |
| COMPLETED, FAILED, CANCELLED | Nincs        | Terminális állapot és outcome immutable                                     |

PENDING -> COMPLETED, RUNNING -> PENDING, terminális újranyitás és más terminális
eredményre váltás tiltott. Azonos command replayje nem transition.
Nincs új status és nincs resumable WAITING_FOR_HUMAN.

Minden módosító parancs backend `command_id`-t és kanonikus command fingerprintet
kap. Ugyanazon run/command azonos művelete csak egyszer commitol; eltérő tartalom
ugyanazon identityvel elutasított. Jogosultsági/technikai authority ellenőrzés
megelőzi a replay disclosure-t. A replay nem indít új végrehajtást.

Sorrend: saját tranzakció -> case lock -> run lock -> a művelethez szükséges friss
user vagy szűk backend authority -> command/event lookup és terminal check ->
új claim/completion esetén fresh current observation -> conditional write és audit -> commit.
Már commitolt command replayje nem új eligibility-vizsgálat vagy végrehajtás;
lezárt case mellett is csak az aktuálisan engedett technikai outcome oldható fel.
A case identity előzetes feloldása csak routing hint; a bindingot a zár után újra
ellenőrizni kell. Nincs resolver snapshot a case lock előtt.
Worker write predicate: várt aktív status, azonos `claim_id` és token,
érvényes deadline, valamint a szükséges counter/command feltételek.
Az affected-row eredmény kötelezően ellenőrzendő; sikertelen predicate nem success.
User cancellation a kezdeményező friss permissionjeivel engedélyezhető;
backend termination/recovery csak a saját szűk, ellenőrzött command authorityjével,
ugyanezen lock sorrend és conditional predicate mellett. Nem impersonálhatja a
usert, nem használ lejárt worker tokent, és nem engedélyezhet új worköt.

Már terminális runnál az első commit eredménye végleges. Aktív runnál a sikeres
completion előtt sorrendben deadline, live actor/permission, case eligibility,
elfogadott cancellation és result validation ellenőrzendő. Határidőnél
`now >= execution_deadline_at` már lejárt. Az egyszerre beadott parancsokat a zár
sorba rendezi; egy korábban commitolt cancellationt completion nem írhat felül.
A megfigyelés nem garantál race-free permission revocationt vagy készletfoglalást.

Ez a több egyidejű stop reason közötti prioritás továbbra PROPOSED. A user
elfogadott osztályozási elve nem döntötte el például a deadline és a bizonyított
case-change versenyének sorrendjét; nem teszi automatikusan elfogadottá az
ELV1-02 arbitration policyját.

### ELV1-03 — Megszűnési okok és terminal mapping

Az ellenőrzés bizonytalansága nem jelenti az üzleti probléma megoldását.
A FAILED azt fejezi ki, hogy nem végezhető el megbízható vizsgálat; a CANCELLED
igazolt okból történő megszakítást jelent, a vizsgálat befejezése nélkül.
Egyik sem módosít automatikusan Problem Case-t, és egyik sem ad procurement,
inventory vagy production végrehajtási jogot. Az AI egyik status alapján sem
oldhatja meg önállóan az üzleti problémát.

**ACCEPTED felhasználói osztályozási elv, csak lifecycle designra:**

| Igazolt helyzet                                                                        | MerlinRun status | Az elfogadás határa                                        |
| -------------------------------------------------------------------------------------- | ---------------- | ---------------------------------------------------------- |
| Current eligibility vagy authoritative business state nem állapítható meg megbízhatóan | FAILED           | Nem igazolható vizsgálati alap; nem bizonyított resolution |
| Az alapul szolgáló probléma bizonyíthatóan megoldódott                                 | CANCELLED        | Bizonyított megszűnési ok, nem befejezett AI-vizsgálat     |
| A vizsgálati alap bizonyíthatóan invalidált vagy már nem alkalmazható                  | CANCELLED        | Igazolt érvénytelenség, nem feltételezett változás         |

Az ELV1-03 osztályozási policyjából ez a három szabály elfogadott. A pontos reason
code-ok, a permission/user megszűnés, explicit cancellation és infrastruktúrahiba
mappingje, valamint a deadline reason/versenyhelyzet részletei továbbra PROPOSED.
Az egész ADR architekturális irányának elfogadása nem terjed ki ezekre a nyitott
policykre. A status topology az ADR 0017 ACCEPTED szerződése marad; új status nincs.

**Megtartott alternatívák a még nyitott policykre:** általános FAILED minden
technikai okra; a specification korábbi PROPOSED mappingjeinek pontosítása.
**PROPOSED technikai mapping:** az elfogadott osztályozás és az ettől külön
felülvizsgálandó meglévő javaslatok az alábbi táblázatban külön szerepelnek.

| Esemény                                                                                        | Status    | Javasolt stop reason                | Besorolás alapja                                                              |
| ---------------------------------------------------------------------------------------------- | --------- | ----------------------------------- | ----------------------------------------------------------------------------- |
| Validált advisory befejezés                                                                    | COMPLETED | `COMPLETED`                         | Megtartott proposal                                                           |
| Advisory lezárás emberi információigénnyel, igazolt backend eligibility mellett                | COMPLETED | `HUMAN_INPUT_REQUIRED`              | Megtartott proposal; nem uncertain backend state                              |
| Authoritative current state nem állapítható meg megbízhatóan                                   | FAILED    | `AUTHORITATIVE_STATE_UNDETERMINED`  | ACCEPTED osztályozás, PROPOSED reason                                         |
| Incomplete, hiányzó vagy unavailable evaluation nem igazolja az eligibilityt                   | FAILED    | `EVALUATION_UNAVAILABLE`            | ACCEPTED osztályozás, PROPOSED reason                                         |
| Fresh eligibility egyéb okból nem állapítható meg                                              | FAILED    | `ELIGIBILITY_UNDETERMINED`          | ACCEPTED osztályozás, PROPOSED reason                                         |
| Authoritative current shortage bizonyítottan RESOLVED                                          | CANCELLED | `CASE_RESOLVED`                     | ACCEPTED osztályozás, PROPOSED reason                                         |
| Source bizonyítottan INVALID vagy case bizonyítottan INVALIDATED                               | CANCELLED | `CASE_INVALID`                      | ACCEPTED osztályozás, PROPOSED reason                                         |
| Investigation basis bizonyítottan superseded és már nem alkalmazható                           | CANCELLED | `INVESTIGATION_BASIS_SUPERSEDED`    | ACCEPTED no-longer-applicable osztályozás, PROPOSED reason/predicate          |
| Case CLOSED                                                                                    | CANCELLED | `CASE_CLOSED`                       | Megtartott proposal; closure önmagában nem bizonyít resolutiont               |
| Szükséges permission visszavonása                                                              | CANCELLED | `AUTHORIZATION_REVOKED`             | Megtartott proposal; nem az új elvből levezetett policy                       |
| Live initiating user törölt/hiányzik                                                           | CANCELLED | `USER_UNAVAILABLE`                  | Megtartott proposal; ELV1-05 szerint                                          |
| Live/historical actor binding sérült                                                           | FAILED    | `ACTOR_BINDING_INVALID`             | Megtartott proposal; nem user-deactivation                                    |
| Immutable case/requirement identity eltér, nincs igazolt supersession                          | FAILED    | `CASE_CONTEXT_MISMATCH`             | Megtartott proposal; eltérés nem bizonyít érvénytelenséget                    |
| Model-turn/tool-attempt keret elfogy                                                           | FAILED    | `STEP_LIMIT_REACHED`                | Megtartott proposal                                                           |
| Worker execution deadline/azonos fix lease lejár                                               | FAILED    | `TIME_LIMIT_REACHED`                | Megtartott proposal                                                           |
| Elfogadott pending deadline lejár claim nélkül                                                 | FAILED    | `PENDING_DEADLINE_EXCEEDED`         | Megtartott proposal; időérték OPEN                                            |
| Tool technikai hiba vagy capability denial                                                     | FAILED    | `TOOL_FAILED` / `TOOL_DENIED`       | Megtartott proposal; revocation mapping külön                                 |
| Jövőbeli model/provider hiba vagy timeout                                                      | FAILED    | `MODEL_FAILED` / `PROVIDER_TIMEOUT` | Megtartott proposal; paid boundary változatlan                                |
| Jogosult explicit cancellation                                                                 | CANCELLED | `CANCELLED_BY_USER`                 | Megtartott proposal; saját authority kell                                     |
| Ismert runtime infrastruktúrahiba, rendezett transaction outcome és működő persistence mellett | FAILED    | `RUNTIME_INFRASTRUCTURE_FAILED`     | PROPOSED technikai pontosítás; nem az elfogadott classificationből következik |

Nem állapítható meg resolution egy resolver kivételéből, hiányzó eredményből,
null evaluationből vagy nem authoritative számból. Supersessionhöz explicit,
trusted backend bizonyíték kell az eredeti alap alkalmazhatatlanságáról;
újabb timestamp, másik case létezése vagy model állítás nem elegendő.
A jelenlegi `ProblemCaseLifecycle` csak OPEN/CLOSED/INVALIDATED értékeket ismer.
Nincs ellenőrzött általános supersession predicate; ennek contractja OPEN-10.
Igazolás hiányában uncertainty/mismatch ág szükséges, nem CANCELLED feltételezés.

**Pontos specification eltérés és későbbi alignment:** az
[Investigation Foundation V1 7.6. része](../../docs/specifications/merlin/investigation-foundation-v1.md#76-proposed--későbbi-runtime-döntések), 505. sor, `Current state UNDETERMINED` sor:
`COMPLETED / HUMAN_INPUT_REQUIRED`. A 3. rész 121–125. sora szintén PROPOSED
mappingként különíti el a reason-párokat az ACCEPTED topologytól.
A most elfogadott user elv megbízható authoritative state hiányában FAILED-ot
követel: a bizonytalanság nem resolution és nem igazolt megszűnési ok.
A régi COMPLETED proposal sem állította önmagában a business problem megoldását;
az eltérés a megbízható vizsgálat terminal osztályozásáról szól.

Az ADR 0018 most rögzített elfogadását követően, külön engedélyezett dokumentációs alignment során a
specification 7.6. UNDETERMINED sorát és kapcsolódó uncertainty szövegeit kell
összehangolni, a reason code-ok véglegesítése mellett. Az advisory-only
information gap `HUMAN_INPUT_REQUIRED` proposalját ettől külön kell kezelni.
ADR 0017 runtime mappingre mutató hivatkozásait akkor újra ellenőrizni kell;
az öt status és a topology nem változik. ADR 0017 és a specification ebben a
feladatban nem módosul, a régi javaslat nincs már frissítettként feltüntetve.

**Persistence határ:** a jelenlegi migration 60–89. sorának CHECK-jei az öt
statushoz timestampet és bounded resultot kötnek; ezek a fenti terminal
osztályozást lehetővé teszik, de a reason policyt nem enforce-olják.
FAILED-nél és CANCELLED-nél kötelező `completed_at` és `stop_reason`;
CANCELLED-nél `cancelled_at=completed_at` is kell. PENDING failure/cancel esetén
a valódi execution hiányát nem szabad kitalált `started_at`-tal elfedni.
PENDING-ből közvetlen COMPLETED továbbra tiltott.

**Infrastruktúrahiba kivételes perzisztálási helyzete:** audit/DB failure nem
garantál mentett FAILED runt. Ha a kötelező event nem menthető, a transition
rollbackol; uncertain COMMIT után előbb ELV1-08 reconciliation kell.
Ismert, lezárt transaction outcome és helyreállt persistence mellett a
feljogosított backend külön, atomikus failure eventtel zárhat; a még nyitott
failure-recovery authority részletei elfogadást igényelnek. Lejárt worker token
vagy puszta exception nem engedhet feltétel nélküli state overwrite-ot.

### ELV1-04 — Friss finalization authorization

Eredményt csak a még jogosult futás fogadhat el. A korábbi indítási engedély nem
marad örök olvasási vagy végrehajtási engedély.

**Alternatívák:** admissionkori permission snapshot; csak tool-boundary check;
claim/tool/finalization friss ellenőrzése. **Javaslat:** mindhárom boundary friss
ellenőrzése. Finalizationnél owner/deadline, live actor, mindkét permission,
current case gate, cancellation és evidence/result validáció szükséges.

Normál COMPLETED result csak e feltételekkel írható. Visszavont permission vagy
hiányzó user esetén a belső backend lezárhat sanitizált status/reason/timestamp
adatot, advice és supplier payload nélkül; ezt nem a volt user nevében teszi.
Történeti result disclosure továbbra is kezdeményezőazonosságot és mindkét aktuális
permissiont igényel. A technikai command response nem általános result-read API.
A `historical_actor_id` provenance, nem authorization. A tárolt recommendation
nem human approval, és nem jogosít procurement vagy más business executionre.
Eltérő case állapotot nem szabad régi observationnel aktuálisként megjeleníteni.

Ha a finalization gate nem ad megbízható authoritative state-et, az ELV1-03
elfogadott FAILED osztályozását kell alkalmazni; bizonyított resolution vagy
alkalmazhatatlan basis esetén CANCELLED szükséges. Ez nem ír át korábban már
commitolt terminális outcome-ot. A termination metadata mentése is authorityt,
conditional state update-et és atomikus execution eventet igényel.

### ELV1-05 — User eligibility és recovery authority

A jelenlegi user jogosultságát a tényleges User és Gate szabályok döntik el.
Employee státuszból nem vezetünk le új user-deaktiválást.

**Ellenőrzött alap:** a [User schema](../../database/migrations/0001_01_01_000000_create_users_table.php),
[User model](../../app/Models/User.php) és [fresh repository lookup](../../app/Repositories/Admin/UserRepository.php)
nem definiál user `is_active`/deactivation predicate-et. A jelenlegi V1 feltétel:
létező live user, egyező initiating/historical identity, aktuális
`inventory.view` AND `item-suppliers.view`, a meglévő Gate-konvencióval.
Super-admin a permission-részt teljesítheti, ownershipot/allowlistet nem kerül meg.
Új email-verification vagy employee-active feltételt ez a tervezet nem vezet be.

**Alternatívák:** employee flag átvétele; új user státuszrendszer; meglévő ellenőrzött
policy és explicit OPEN korlát. **Javaslat:** utóbbi. A valódi deactivation policy
OPEN, külön security/domain döntés kell hozzá.

| Authority                 | Mire jogosít a proposal szerint?                                                                               |
| ------------------------- | -------------------------------------------------------------------------------------------------------------- |
| Initiating user           | Saját run explicit cancellationje friss két permissionnel; protected result read külön read boundaryn          |
| Worker technical identity | Adott run claimjének használata; nincs user permission helyettesítés                                           |
| Backend recovery          | Belső, deployment által kontrollált recovery entry; csak igazolt expiry/pending deadline és sanitizált lezárás |
| Modell/tool input         | Nem képez actort, claim tokent, recovery authorityt vagy allowlistet                                           |

Recovery nem kap általános supplier/result disclosure-t és nem adhat új execution
jogot. A belső entry indításának operatív hozzáférését és felelősét ELV1-07
OPEN pontjai szerint kell rögzíteni. Új publikus vagy tetszőleges caller által
meghívható recovery/cancellation endpoint nem része a tervnek.

### ELV1-06 — Dedikált append-only execution audit

A futás eseményeit saját napló őrzi. Az indulási audit és az üzleti Evaluation
History megmarad a saját céljánál.

**Alternatívák:** opcionális activity log; admission audit/history kibővítése;
dedikált execution event struktúra. **Javaslat:** tervezett
`merlin_run_execution_events`, kötelező szinkron írással.

| Logikai mező/csoport                               | Kötelező jelentés                                                                      |
| -------------------------------------------------- | -------------------------------------------------------------------------------------- |
| `event_id`, `run_id`, `sequence`, `schema_version` | Backend UUID event/run, runon belüli monoton event sorrend és version                  |
| `event_type`, `event_at`                           | Zárt backend eseménykészlet és UTC idő                                                 |
| `from_status`, `to_status`, `stop_reason`          | Transition provenance; nem-transition eventnél a megfelelő mezők null                  |
| `command_id`, `command_fingerprint`                | Command replay és eltérő tartalmú identity-ütközés felismerése                         |
| `claim_id`, `authority_kind`                       | Worker/cancellation/recovery correlation; nincs titkos token                           |
| `call_id`, `attempt_kind`, `attempt_number`        | Reservation és call outcome összekötése, külön model/tool számlálás                    |
| `metadata_schema_version`, `metadata`              | Engedélyezett, méretkorlátos, sanitizált JSON; technikai hiba kódja, nem raw exception |

Egy parancsnak több eventje lehet: unique `(run_id, command_id, event_type)`;
egy adott call/kind reservationje külön unique identityt kap. Unique
`(run_id, sequence)` és run FK szükséges. Konkrét DDL, string/metadata byte
korlátok és indexnevek a persistence-szelet előtt pontosítandók; korlát nélküli
JSON nem elfogadható. A command fingerprint nem titkos token vagy teljes
supplier-commercial payload naplózásának ürügye.

Egy reservation command egy callhoz tartozik; több tool request külön commandot
kap. Reservation és outcome rögzítése külön parancs lehet, ugyanazzal a `call_id`-val.
Ez megakadályozza, hogy egy batch több azonos típusú eventje a command unique
kulcsa miatt elveszítse a külön attempt identityt.

Példa eventek: `run.claimed.v1`, `attempt.reserved.v1`, `attempt.finished.v1`,
`run.terminated.v1`, `run.recovered.v1`. Event, counter és state változása
ugyanazon owned connection ugyanazon tranzakciójában commitol vagy rollbackol.
Nincs független connectionön előre elmentett success audit.

Append-only normál runtime alatt: nincs update/delete API. A jogszerű retention
cleanup külön kivétel; investigation-owned event/evidence a terminális lezárástól
számított 365 napos retentiont és lawful holdot követi. Run törlése csak kontrollált
dependency sorrenddel történhet; üzleti history/case nem törlődik. Admission audit
változatlan, human decision és tényleges business action külön későbbi rekord.

### ELV1-07 — Dispatch és pending recovery

A befogadott run és a queue üzenet két külön tény. Ha a folyamat közöttük leáll,
a runnak ellenőrzött módon újra megtalálhatónak kell maradnia.

| Alternatíva                                               | Előny                                                      | Korlát                                                              |
| --------------------------------------------------------- | ---------------------------------------------------------- | ------------------------------------------------------------------- |
| `afterCommit` callback                                    | Nem indul commit előtt worker                              | Commit és enqueue közötti crash ablak megmarad                      |
| Explicit enqueue committed runra + pending reconciliation | Kis V1 felület, duplicate delivery biztonságosan kezelhető | Szükséges tartós pending policy, scanner és üzemeltetési bizonyíték |
| Transactional outbox                                      | Tartós dispatch-intent és ismételhető publisher            | Új tábla/publisher/monitoring; nem exactly-once execution           |

**Javaslat:** explicit belső enqueue már commitolt admissionre, külön kontrollált
pending reconciliationnel. Az admission tranzakcióban nincs queue/provider hívás.
Queue payload csak run UUID és technikai message correlation; user/model input
nem adhat actor/claim authorityt. A jelenlegi `after_commit=false` globális
beállításra nem támaszkodhat a későbbi dispatcher.

Commit utáni, enqueue előtti crash esetén a scanner a tartós PENDING, még nem
claimelt runt újra felajánlhatja a queue-nak. Ehhez rövid dispatch reservation,
korlátos enqueue próbálkozás és durable dispatch identity/attempt metadata kell;
queue round-trip nem történhet case/run zár tartása közben. A dispatch reservation
lejáratából legfeljebb új üzenet következhet, nem worker takeover. Több üzenetből
csak egy claim sikerülhet. Claim nélkül elért, előre definiált
`pending_deadline_at` után a backend FAILED-ra zár; queue wait ettől még nem
fogyasztja a 180 másodperces running keretet.

**OPEN aktiválási kapu:** pending deadline értéke/eredete és régi PENDING runok
kezelése; dispatch reservation TTL, retry/backoff/attempt limit; scanner ütemezés,
felelős és recovery entry hozzáférés; broker delivery elvárás. Ezek nélkül a
fenti mechanizmus csak proposal, queue adapter nem fogadható el implementáltként.
Ha tartós dispatch-intent garancia szükséges, ELV1-07-et outboxra kell újra dönteni.

Kötelező operatív kapcsolat: maximális queue reservation-to-exit idő < broker
visibility/`retry_after`, és a worker hard timeout engedje a 180 másodperces
execution határ utáni korlátos cleanupot. A rövidebb broker visibility fencinggel
kezelhető duplicate-et okozhat, de nem tekinthető kész deployment policynek.
A jelenlegi 90 másodperces default nem bizonyít illeszkedést. Windows process
termination, transport timeout és supervisor viselkedése külön igazolandó;
nincs feltételezett `pcntl` garancia. Ezek konkrét értékei OPEN.

### ELV1-08 — Retry és bizonytalan commit

Csak azt szabad ismételni, amiről tudjuk, hogy nem maradt tartós vagy külső hatása.
Az elveszett commit-válasz nem bizonyít rollbackot.

**Alternatívák:** általános queue/transaction retry; minden retry tiltása;
operation identity alapján osztályozott retry. **Javaslat:** utóbbi, kezdetben
automatikus adatbázis-retry nélkül; később csak bizonyított teljes rollback utáni,
database-only művelet kaphat explicit korlátos retry policyt.

| Művelet/hiba                            | V1 javaslat                                                                                    |
| --------------------------------------- | ---------------------------------------------------------------------------------------------- |
| Bizonyított rollback, nincs külső hívás | Friss owned transaction és authority mellett azonos commanddal explicit újrapróba megengedhető |
| Bizonytalan COMMIT                      | Új connectionön operation/event/state reconciliation; addig nincs új külső munka               |
| Worker crash/redelivery                 | Nincs új execution claim, resume vagy counter reset                                            |
| Tool invocation                         | Ismeretlen outcome nem ismételhető automatikusan; reserved attempt megmarad                    |
| Jövőbeli paid/provider invocation       | Nincs automatikus repeat vagy usage-mentesnek feltételezett timeout                            |

Reconciliation azonos run/command/fingerprint és claim/event adatokból történik,
friss user authorizationnel, illetve szűk backend recovery authorityvel. Hiányzó
event egyetlen olvasása nem bizonyít rollbackot: az eredeti tranzakció végső
állapotát és a unique identityt is rendezni kell. Unresolved állapotban stop és
operatív vizsgálat kell, nem garantált siker/rollback válasz.
Claim reconciliation csak az eredeti, tokent birtokló folyamat azonos műveletét
igazolhatja; új workernek nem ad takeover jogot. Ha a folyamat elveszett, expiry
utáni recovery következik. Reservation reconciliation nem indítja újra az
esetleg már elküldött callt; annak kimenetele külön ismert/unknown tény.

A [meglévő ack-loss teszt](../../tests/Feature/MerlinAdmissionServiceTest.php)
413–428. sora sikeres commit után injektál kivételt. Valódi mid-COMMIT network
connection loss nincs ezzel igazolva, és ebben a tervezési feladatban sem tesztelt.

### ELV1-09 — Runtime limit enforcement

A keretet a backend foglalja le a próbálkozás előtt. A modell együttműködése és
az újra kézbesített job nem módosíthatja a számlálókat.

**Alternatívák:** in-memory/prompt counter; hívás utáni számlálás; tartós atomic
reservation. **Javaslat:** runhoz kötött `model_turns_used` és
`tool_call_attempts_used`, feltételes increment és reservation event együtt.

| Esemény                                         | Számlálás                                                                            |
| ----------------------------------------------- | ------------------------------------------------------------------------------------ |
| Új model attempt                                | Dispatch előtt egy turn, maximum 8                                                   |
| Rejected/malformed/failed model attempt         | A lefoglalt turn elfogyott; nincs refund                                             |
| Új tool request, denied/malformed/failed is     | Egy tool attempt, maximum 4; nincs refund                                            |
| Egy model response több tool requesttel         | Mindegyik külön attempt; csak a keretbe férő request kaphat reservationt             |
| Azonos call identity, azonos tartalom duplicate | Nincs új reservation vagy dispatch; már ismert outcome oldható fel                   |
| Azonos call identity, eltérő tartalom           | Protocol/idempotency hiba; nincs call, nem tekinthető az eredeti sikeres replayjének |
| Eltérő identityvel újra kért azonos tartalom    | Új attempt; nem lehet ingyenes retry                                                 |
| Kimerült keret után új kérés                    | Nincs counter túllépés vagy dispatch; FAILED/STEP_LIMIT_REACHED                      |

Call identity és request sorrend backend-owned; model által tetszőlegesen
újrahasznált ID nem adhat keretkerülést. A rossz formátumú request is backend
attempt identityt kap. Duplicate queue delivery nem model/tool attempt.
Az utolsó megengedett válasz még finalizálható; pusztán a 8./4. reservation nem
hiba, a kereten túli új attempt tiltott.

Reservation és response acceptance előtt status/token/cancellation/deadline
ellenőrzés kell. A befogadott új tool request reservationje a tool input/allowlist/
permission eredményétől függetlenül fogyasztja az attemptet; az ezután kapott
DENIED vagy malformed outcome nem ad refundot. Már lezárt vagy lejárt runból
érkező request nem juthat új reservationig. Dispatch előtt friss authorization
és current gate kell; közben észlelt revocation sanitizált backend terminationt
eredményez, nem supplier disclosure-t. Külső hívás timeoutja legfeljebb a fennmaradó execution idő;
nem hosszabbíthatja a deadline-t. A tartós deadline összehasonlítása egységes
UTC időforrással, az írás pillanatához kötve történjen; process-local monotonic
óra a blokkoló hívás időkeretéhez használható, nem cross-process authority.
Az SQL időpontosság és clock policy a kétmotoros persistence tervben rögzítendő.

### ELV1-10 — Paid execution határ

Fizetős inference nem indulhat. A lifecycle elkészítése nem ad provider-választási
vagy költési engedélyt.

**Alternatívák:** limitált, de monetary policy nélküli provider call; disabled paid
execution és külön döntés. **Javaslat:** utóbbi. A jelenlegi
`paid_execution_enabled=false` és `cost_ceiling.state=NOT_CONFIGURED` megmarad;
runtime dispatcher is fail closed legyen. User/model request vagy queue retry nem
írhatja át. A fake adapter tesztelési eszköz, nem élő model execution.

Provider/model, monetary ceiling, token/request/output korlátok, usage accounting,
cost reservation/reconciliation és unknown usage kezelése külön ACCEPTED döntést
és ellenőrzött implementációt igényel. Ár vagy provider nincs kiválasztva.
ADR 0005 Python/inference és Laravel/business határát ez a terv nem módosítja.

## 5. Authorization és revocation checkpointok

Minden jelentős határ új ellenőrzést igényel. Egy korábbi availability vagy
admission observation nem tartós engedély.

| Boundary                         | Ellenőrzés                                                                                         |
| -------------------------------- | -------------------------------------------------------------------------------------------------- |
| Admission                        | Meglévő Auth guard + fresh két permission; case lock utáni ismétlés változatlan                    |
| Claim                            | Persisted run binding, live user, két permission, fresh OPEN/VALID/ACTIVE és exact pozitív netting |
| Tool discovery                   | Backend allowlist és fresh user; a modell nem állítja össze a trusted contextet                    |
| Minden tool invocation           | Owner/counter/deadline előkapu, majd a meglévő tool saját authorization és current gate-je         |
| Response acceptance/finalization | Owner, deadline, cancellation, friss authorization/eligibility, schema/evidence validation         |
| User cancellation                | Kezdeményezőazonosság + aktuális két permission; más useré super-adminként sem                     |
| Recovery                         | Belső technikai authority + ellenőrzött lejárt ownership/pending deadline; nincs advice disclosure |

Source INVALID nem RESOLVED, UNDETERMINED nem nulla hiány. Case supersessionre
nincs új status vagy automatikus rebind. Igazolt, már nem alkalmazható basis
CANCELLED; bizonytalan basis/evaluation FAILED. Puszta identity mismatch nem
supersession-bizonyíték. A trusted predicate OPEN-10 szerint véglegesítendő.

## 6. Ownership, persistence és tranzakcióhatár

A tartós run adja a worker jogának igazolását; a queue csak értesítés. A javasolt
új run metadata: egyszer beállított claim identity/token hash, immutable deadline,
model/tool counter és a pending/dispatch policy szükséges metadataja.
Ezek még nem létező schema mezők. A lifecycle service a repository interface
kiterjesztésén keresztül dolgozik, nem controllerből vagy jobból végzett raw update-tel.

Minden claim/reservation/transition és kötelező event közös commit. Nincs
automatikus reconnect vagy rejtett PDO-csere nyitott owned transaction alatt.
A hosszú munka a commit után történik; az eredmény új rövid tranzakcióban tér vissza.
A jelenlegi tool caller-transaction tiltása megmarad. A transaction végén a
process snapshot nem él tovább authorityként. Worker írás tokenellenőrzés nélkül
nem jóváhagyott lifecycle út; közvetlen bulk/raw SQL vagy Eloquent save nem
kerülheti meg a service conditional predicate-jeit. Backend cancellation/recovery
nem a worker tokenjét kapja meg: saját szűk authorityjét, a várt aktív state-et
és claim identityt, recoverynél a megfelelő expiry/pending deadline bizonyítékot
kell ellenőriznie. Általános infrastruktúra-exception nem helyettesíti ezeket.
Sem user permission elvesztése, sem backend technical identity nem jogosít új
claimre, külső munkára vagy terminal overwrite-ra. A failure utáni lezárás még
nyitott részleteit OPEN-07a rögzíti.

## 7. Failure és recovery mátrix

A recovery lezárást végez; nem indítja újra a vizsgálatot. Adatbázis-kiesés alatt
nem állítható, hogy a lezárás vagy az audit már tartósan megtörtént.

Az uncertainty -> FAILED és a bizonyított resolution/alkalmazhatatlanság ->
CANCELLED osztályozás ACCEPTED. A többi failure/retry/authority kezelés ebben a
mátrixban PROPOSED; az elfogadott elv nem dönti el helyettük a policyt.

| Helyzet                           | Állapot/kezelés                                                | Retry/idempotencia és audit                                                                     |
| --------------------------------- | -------------------------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| Worker termination/crash          | RUNNING a fix lease lejártáig, utána FAILED/TIME_LIMIT_REACHED | Nincs takeover; conditional recovery + event; elakadásnál operatív segítség                     |
| Queue delivery hiba               | PENDING; definiált pending deadline után FAILED                | Korlátos dispatch reconciliation, nem execution retry; tartós attempts                          |
| Duplicate/concurrent worker       | Egy claim; többi nincs új work                                 | Claim/command identity lookup; nincs új counter vagy history                                    |
| Deadlock/lock timeout             | Utolsó ismert commitolt state marad                            | Csak bizonyított rollback database-only retryja; nincs generikus admission retry                |
| DB disconnect / uncertain COMMIT  | Nincs garantált success vagy rollback                          | Új authorized operation reconciliation; unresolved esetben stop/operatív vizsgálat              |
| Lease lejár / stale result        | FAILED recovery; késői result elutasított                      | Nincs state/result overwrite vagy új call; recovery event                                       |
| Future provider timeout/error     | FAILED megfelelő reasonnel, ha menthető                        | Nincs automatikus paid replay; outcome/usage unknown nem nulla költség                          |
| Tool error/denial                 | FAILED/TOOL_FAILED vagy TOOL_DENIED                            | Reserved attempt megmarad; permission revocation észlelésekor CANCELLED mapping elsőbbséget kap |
| Permission/user megszűnik         | CANCELLED; csak sanitizált backend metadata                    | Nincs új tool/disclosure; fresh lookup és termination event                                     |
| Case closed/invalid/resolved      | CANCELLED megfelelő reasonnel                                  | Nem módosít business historyt; distinct observation és event                                    |
| Eligibility undetermined/mismatch | FAILED                                                         | Nincs feltételezett current fact; szükség esetén emberi vizsgálat                               |
| Explicit cancellation             | CANCELLED, token többé nem használható                         | Ismételt command idempotens; in-flight work kooperatív, late result eldobandó                   |
| Audit insert failure              | A state/counter update rollbackol                              | Nincs névleges success/FAILED mentés; helyreállás után kontrollált recovery                     |

Confirmed superseded basis esetén CANCELLED és a javasolt
`INVESTIGATION_BASIS_SUPERSEDED` reason alkalmazható; bizonytalan supersession
vagy incomplete/unavailable evaluation esetén FAILED, nem CASE_RESOLVED.
Ismert runtime infrastruktúrahiba rendezett transaction outcome mellett a
PROPOSED FAILED/RUNTIME_INFRASTRUCTURE_FAILED ágra vezethet, kizárólag
engedett conditional transitionnel és kötelező audittal. Uncertain COMMIT vagy
nem működő audit esetén előbb reconciliation/helyreállítás kell; nincs fiktív lezárás.

## 8. Biztonsági invariánsok

A lifecycle csak a vizsgálatot vezérli; új üzleti authorityt nem teremt.

- Effective Merlin Permission = User Permission ∩ AI Permission ∩ Case Context.
- Claim token, actor, allowlist, run/case binding és command identity backend-owned.
- AI output untrusted; validált schema és runhoz/case-hez tartozó evidence reference kell.
- Recommendation nem human decision; human decision nem helyettesíti a külön business workflow authorizationt.
- Nincs supplier ranking/selection vagy közvetlen business write; exact decimal és UNKNOWN szemantika megmarad.
- Nincs default full prompt/raw response, secret, SQL vagy stack trace az execution recordban.
- Terminális result immutable; protected readhez kezdeményezőazonosság és aktuális két permission kell, nem érvényes worker lease.
- Az OCR általános confidence guidance nem hoz létre Merlin confidence százalékot; ADR 0017 specifikus határa érvényes.

## 9. MySQL és SQLite viselkedés

A két adatbázis ugyanazt az üzleti invariánst külön zárolási eszközzel védi.
SQLite siker önmagában nem MySQL konkurenciabizonyíték.

| Terület        | MySQL                                                                  | SQLite                                                                            |
| -------------- | ---------------------------------------------------------------------- | --------------------------------------------------------------------------------- |
| Írási boundary | Saját InnoDB transaction, explicit isolation/access policy és row lock | Saját file-backed connection, IMMEDIATE write intent a domain read előtt          |
| Lock order     | Case -> run -> friss authorization/read -> event                       | Az írói intent után ugyanaz a logikai sorrend; FOR UPDATE nem ad MySQL row lockot |
| Claim/finalize | Conditional predicate + affected rows + event atomicity                | Ugyanaz a predicate/event invariáns; rövid write transaction                      |
| Contentions    | Real row-lock wait, deadlock és timeout külön tesztelendő              | Busy/locked explicit hiba, nem implicit execution retry                           |
| Schema         | Native CHECK/FK/unique és explicit InnoDB                              | CHECK/FK enforcement és kétmotoros migration round-trip                           |
| Tesztadat      | Csak projekt guard által engedett dedikált MySQL test DB               | Admission/lifecycle concurrencyhoz file-backed testing DB; nem izolált :memory:   |

DB időforrás, timestamp precision és affected-row driver-viselkedés a persistence
szeletben explicit tesztelendő. Nem változik globálisan a database/queue config
ebben az ADR-feladatban; jövőbeli eltéréshez külön implementációs scope kell.

## 10. Tesztstratégia és quality gate-ek

Most dokumentációt ellenőrzünk. Az alábbiak jövőbeli elfogadási követelmények,
nem lefutott vagy sikeres lifecycle tesztek.

| Szint                           | Szükséges bizonyíték                                                                                                                    |
| ------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| Determinisztikus unit           | Minden allowed/forbidden transition, reason mapping, deadline equality, 8/4 counter boundary, command/call dedupe, result schema        |
| SQLite integration              | File-backed IMMEDIATE claim; duplicate delivery; audit/state/counter rollback; revoked permission és case-change; terminal immutability |
| MySQL concurrency               | Két competing claim; cancellation/completion és recovery/late-result race; lock wait után fresh authorization; deadlock/timeout         |
| Process/fake adapter            | Kill claim előtt/után, crash reservation után, pending enqueue gap, redelivery; nincs külső model call                                  |
| Idempotency/fault injection     | Claim/reservation/finalization command replay, eltérő fingerprint; post-commit injected exception utáni reconciliation                  |
| Külön infrastruktúra fault test | Valódi mid-COMMIT connection loss; nem helyettesíthető post-commit exceptionnel                                                         |
| Regresszió                      | Meglévő Merlin admission/persistence/migration, supplier tool és resolver tesztek; változatlan business projection/history              |

**Audit finding — implementációs readiness kapu:** a
[quality-gate matrix](../../config/quality-gates.php) nem sorol Merlin suite-ot
modulhoz. Az [AffectedSelector](../../app/Support/QualityGate/AffectedSelector.php)
96–98. sora ismeretlen source-ra integration fallbackot választ; a
[GatePlanner::integration](../../app/Support/QualityGate/GatePlanner.php)
108–121. sora modul/integration listából épít backend parancsot, és nem egyesíti
a kiválasztott changed backend teszteket. Így a Merlin tesztek kimaradhatnak.
Ezt a readiness audit in-memory command-plan ellenőrzése is jelezte; új
DB-tesztfutási bizonyíték nincs ebben az ADR-ben.

Lifecycle implementáció csak a gate kiválasztás javítása vagy explicit,
ellenőrzött Merlin coverage igazolása után fogadható el. Ebben a feladatban a
quality-gate kód nem változik. A
[layered gates](../../docs/development/quality-gates.md) alapján először a legszűkebb
biztonságos kapu kell; új migrationnél releváns full/migration gate és külön
guardolt MySQL bizonyíték. Rögzíteni kell a tényleges suite/command listát,
assertiont, környezetet és kihagyott ellenőrzések okát. A
[Definition of Done](../../docs/project-management/definition-of-done.md) kötelező.

Környezeti előfeltétel: dedikált guarded test DB, processindítás és writable testing
storage, igazolt Windows timeout/cleanup és üzemeltethető queue/recovery runner.
Ezek rendelkezésre állását ez a documentation-only feladat nem mérte fel.
Windows startup latency mélyebb oka továbbra ismeretlen; a korábban javított
25 másodperces markerhatáridő nem új alkalmazás-domain blocker. Korábbi teszt PASS
számok nem jelentenek lifecycle vagy teljes quality-gate sikert.

## 11. Implementációs szeletek és függőségek

Kis, külön ellenőrizhető lépések következhetnek, kizárólag új implementációs
felhatalmazással. Az ADR elfogadása sem ír kódot és nem aktivál worker futást.

Kötelező javasolt sorrend: L1 lifecycle contract/persistence -> L2 claim és
transition -> L3 cancellation/recovery -> L4 runtime guards és determinisztikus
fake execution -> L5 queue adapter/controlled dispatch. L1 nem indíthat workert,
nem dispatcholhat jobot és nem fogyaszthat model/tool attemptet. L2–L3 csak
izolált service verificationt enged; teljes worker execution L4 biztonsági
előfeltételei és L5 operatív kapui nélkül nem aktiválható. Későbbi szelet nem
kerülheti meg az ownership, authorization, atomic audit vagy runtime-limit kapukat.

| Szelet                             | Scope és függőség                                                                      | Acceptance criteria és szükséges teszt                                               | DoD és kizárás                                                                                    |
| ---------------------------------- | -------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------- |
| L1: persistence és contract        | Elfogadott döntések után claim/deadline/counter/event schema, command/result szerződés | Kétmotoros round-trip, FK/unique/CHECK/bounds; determinisztikus matrix/reason teszt  | Schema/index/clock OPEN pontok feloldva, releváns migration/Pint/PHPStan gate; nincs job/provider |
| L2: lifecycle service              | L1 után atomic claim, conditional transition, event és dedupe                          | Duplicate/competing claim, stale save, terminal race, audit rollback SQLite/MySQL    | Explicit Merlin coverage és gate-finding rendezett; nincs dispatch vagy model call                |
| L3: cancellation/recovery          | L2 után belső authority, expired ownership és pending policy                           | Kill/expiry, cancel-finalize race, late worker és uncertain-operation reconciliation | Pending/operatív OPEN pontok feloldva; nincs takeover/resume/HTTP                                 |
| L4: runtime guards fake adapterrel | L2–L3 után limits, fresh authorization/eligibility, validated outcome                  | 8/4/180, denied/malformed/duplicate accounting, fabricated evidence rejection        | Determinisztikus és kétmotoros regresszió; nincs live inference, általános tool dispatcher        |
| L5: queue adapter                  | L1–L4 és ELV1-07 aktiválási kapu után explicit committed enqueue és scanner            | Crash commit/enqueue között, redelivery, dispatch storm bound, timeout/visibility    | Operatív owner és process evidence, elfogadott delivery policy; paid false, nincs UI/API          |

Minden szeletre: teljes releváns diff review, atomic audit és authorization
bizonyíték, saját kockázatának megfelelő regresszió, dokumentáció és projekt DoD.
Provider, retention/legal-hold implementáció és human decision workflow külön
későbbi scope; ezek hiányát nem szabad a lifecycle V1-be rejtve megoldani.

## 12. Következmények és trade-offok

Az egyszeri claim és fix deadline egyszerűvé teszi a stale worker kizárását.
Ennek ára, hogy egy crash után az adott vizsgálat nem folytatható, és a slot a
kontrollált recoveryig foglalt maradhat. Új runhoz új autorizált admission kell.

A dedikált event struktúra több storage-ot és transaction contractot igényel,
de nem függ opcionális loggingtól. A bounded audit nem teljes conversation replay.
A szigorú finalization recheck eldobhat időközben jogosulatlanná vagy elavulttá
vált advice-ot. Ez nem ígér azonnali provider cancellationt vagy nulla külső költséget.

Az explicit enqueue + scanner kisebb az outboxnál, viszont nem vállal előre
bizonyítatlan delivery SLA-t. A recovery operatív rendelkezésre állása szükséges.
Új infrastruktúra nélküli exactly-once execution és külső inference garancia nincs.

## 13. Kifejezett non-goalok

Ez az ADR sem önmagában, sem az implementációs roadmapen keresztül nem engedélyez:

- HTTP/API endpointot vagy UI-t;
- AI provider integrationt, live vagy paid inference-t;
- procurement executiont, inventory vagy production-plan mutationt;
- autonomous business decisiont vagy általános, unrestricted tool executiont;
- AI/Python közvetlen database accesst;
- új permissiont, user-deactivation rendszert vagy employee-statusból képzett policyt;
- worker takeover/resume-t, új MerlinRun statust vagy generic registry frameworköt;
- retention/legal-hold vagy human approval workflow implementációt.

## 14. OPEN kérdések és külső blokkolók

A user osztályozási elve és az 1. rész architekturális iránya ACCEPTED.
A nyitott kérdéseket az alábbi **kötelező implementációs kapuk** rendezik, nem
oldják meg. Az elfogadás nem terjed ki az egyes kapuk mögötti nyitott részletekre.
P0: Slice 1/L1 implementáció előtt szükséges contract-döntés. P1: worker execution
előtti implementációs/operatív feltétel. P2: későbbi külön szeletre halasztható.
PAID: paid inference előtt szükséges; addig a tiltás feltétlen.
A legkorábbi érintett szelet nem azonos minden későbbi döntés azonnali lezárásával.

| ID       | Nyitott döntés/bizonyíték                                                                                                           | Prioritás és elfogadási kapu                                                                                                                                                  | Legkorábbi érintett szelet                                                     |
| -------- | ----------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| OPEN-01  | Pontos transition/command contract, reason code-ok és a user elv által nem eldöntött mappingek véglegesítése                        | P0: L1 előtt; az uncertainty/resolution/invalidation osztályozás már ACCEPTED, nem újra eldöntendő                                                                            | L1 contract; külön engedélyezett specification alignment                       |
| OPEN-02  | Pending deadline érték/eredet, régi PENDING run policy, dispatch reservation TTL, bounded retry/backoff, broker delivery elvárás    | P1: worker/dispatch előtt; a mezők és nullability contractja P0, a konkrét értékek nem választottak                                                                           | L1 persistence határ; értékek L3, dispatch policy L5                           |
| OPEN-03  | Recovery/scanner operatív owner, indítási hozzáférés, cadence, queue visibility, hard timeout/cleanup és Windows process viselkedés | P1: worker execution előtt; deployment bizonyíték kell, nem elég a queue default                                                                                              | L3 belső entry; L5 aktiválás                                                   |
| OPEN-04  | Megbízható user-deactivation predicate hiánya                                                                                       | P2: külön security/domain döntésre halasztható; jelen V1 a verified live User/Gate policyt használja, employee flag nélkül                                                    | L1 dokumentált user contract; külön későbbi változás                           |
| OPEN-05  | Konkrét schema/index/bounds, command envelope, execution event/evidence contract és UTC clock/deadline representation               | P0: L1 persistence implementáció előtt; nem maradhat korlát nélküli event JSON                                                                                                | L1 contract, evidence grammar is; validátor implementáció L4-ben, worker előtt |
| OPEN-06  | Merlin suite kimaradás a quality-gate selectionből                                                                                  | P0 acceptance gate: a releváns lifecycle implementáció elfogadása előtt javítás vagy explicit verified coverage; ebben a feladatban nincs kódjavítás                          | L1 elfogadása, minden későbbi szeletben újra igazolandó                        |
| OPEN-07a | Unknown command/call outcome és infrastruktúrahiba utáni szűk termination/reconciliation authority pontos szerződése                | P0 a command/authority contract; P1 a bizonyított recovery implementáció                                                                                                      | L1; L3 recovery, L4 call outcome                                               |
| OPEN-07b | Valódi mid-COMMIT network connection-loss teszt bizonyítéka                                                                         | P1: alkalmazandó connection-loss verification és bizonyíték worker előtt; a valódi mid-COMMIT tesztet post-commit injection nem helyettesíti; pontos fault-test contract OPEN | L3 reconciliation verification; worker előtt                                   |
| OPEN-08  | Provider/model, monetary ceiling, accounting, usage reservation/reconciliation és unknown usage                                     | PAID: külön ACCEPTED döntés és bizonyított enforcement paid inference előtt; lifecycle V1 ettől függetlenül csak paid false                                                   | L1/L4 disabled contract; élő provider külön későbbi szelet                     |
| OPEN-09  | Retention cleanup/legal hold, lawful user deletion és human decision workflow implementáció                                         | P2: külön scope/engedély; ACCEPTED retention, provenance és disclosure határok már L1-ben megőrzendők                                                                         | L1 FK/retention dependency; végrehajtás később                                 |
| OPEN-10  | Authoritative supersession/no-longer-applicable bizonyíték és unavailable evaluation reason-szétválasztás pontos predicate-je       | P0: a logikai evidence/uncertainty contract L1 előtt; P1: tényleges predicate bizonyítása worker előtt, nincs feltételezett supersession                                      | L1 contract; L3–L4 ellenőrzés                                                  |

A dokumentációs eltérés nem elfogadott state topology ellentmondása: a
specification 7.6., 505. sorában álló korábbi PROPOSED UNDETERMINED mappinget
kell majd az ELV1-03-ban rögzített elfogadott user elvhez igazítani.
A konkrét reason code-ok és egyéb policyk nyitottak maradnak.

E dokumentumhoz nem társul új migration, teszt, job, konfiguráció vagy runtime.
ADR 0017 és a specification változatlan; ADR 0018 architekturális iránya ACCEPTED,
az OPEN részletek nyitottak maradnak. Az elfogadott ownership/fencing/audit/limit
irány és a hozzá tartozó technikai javaslatok nem már implementált
worker-képességek. A jelenlegi kód az admissiont és storage invariánsokat védi.
