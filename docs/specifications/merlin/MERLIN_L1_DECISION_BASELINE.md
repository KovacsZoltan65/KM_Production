# Merlin L1 — döntési alapállapot

- **Dátum:** 2026-10-21.
- **Állapot:** a felhasználó által elfogadott `L1-D01`–`L1-D14` döntések dokumentációs alapállapota.
- **Ellenőrzött kiindulás:** `feature/merlin-candidate`, `71df227dbfed27101700bad836d30c3bf0c3011c`; tiszta munkakönyvtár és staging, azonos helyi HEAD és helyi távoli követőág.
- **Kapcsolódó mentés:** [Recovery Checkpoint 002](MERLIN_RECOVERY_002.md).

## 1. Cél, hatókör és források

A dokumentum a későbbi L1 lifecycle contract/persistence munka visszaállítható
döntési alapja. A döntések forrása a felhasználó jelen feladatban átadott,
jóváhagyott 14 pontos döntéslistája. Az itt nyitottként jelölt részletek nem
elfogadott implementációs szerződések. Ez a mentés nem implementációs vagy
üzemeltetési engedély.

Az összevetés forrásai:

- [Merlin koncepció és felelősségi határok](README.md).
- [ADR 0017 — Investigation Foundation](../../../.kiro/decisions/0017-merlin-investigation-foundation.md).
- [ADR 0018 — Execution Lifecycle](../../../.kiro/decisions/0018-merlin-execution-lifecycle.md).
- [Investigation Foundation V1 specifikáció](investigation-foundation-v1.md).
- [Recovery Checkpoint 001](MERLIN_RECOVERY_001.md).
- [Rétegezett quality gate-ek](../../development/quality-gates.md),
  [Definition of Done](../../project-management/definition-of-done.md) és
  [commitkonvenció](../../project-management/commit-conventions.md).

Az ADR 0018 az eredeti `MERLIN_RUN_EXECUTION_LIFECYCLE_V1_READINESS` auditot
beszélgetésbeli előzményként említi. Önálló eredeti readiness/L1 auditjelentés
a repository Markdown-forrásainak és fájlneveinek vizsgálatával nem volt
azonosítható. Az ADR összefoglalása és a jelen döntéslista nem helyettesíti
a hiányzó auditbizonyítékot. A meglévő procurement-input audit más tárgyú forrás.

A meglévő admission/persistence képesség és a tervezett worker lifecycle
elkülönül: egy elfogadott terv önmagában nem igazol runtime kontrollt vagy
sikeres lifecycle tesztet. A korábbi dokumentumok teszteredményei történeti
állítások; ebben a feladatban alkalmazásteszt nem futott.

## 2. Elfogadott L1 döntések

### L1-D01 — Lifecycle commandok

Hat elkülönített parancs: `ClaimRun`, `FinalizeRun`, `TerminateFailedRun`,
`CancelRun`, `StopRunForContext`, `RecoverRun`. Eltérő felelősségüket,
jogosultságukat és állapotátmeneteiket meg kell őrizni. A teljes command-,
input/output- és transitionmátrix még véglegesítendő; a nevek nem kész API-k.

### L1-D02 — Adattárolás

Két új kiegészítő tábla: `merlin_run_executions` és
`merlin_run_execution_events`. Az L1 nem építi át a meglévő `merlin_runs`
tábla szerkezetét. Mezők, indexek, kapcsolatok és adatbázis-korlátok a
technikai szerződésben véglegesítendők; itt nincs elfogadott DDL.

### L1-D03 — Időkezelés

A hiteles időforrás az adatbázisóra. Az időábrázolás egész számú UTC epoch
seconds. A 180 másodperces végrehajtási határidő a sikeres `ClaimRun` során
keletkezik, és később nem módosítható. `FinalizeRun` csak
`db_now < execution_deadline` esetén lehet sikeres; egyenlőségnél már nem.
A két adatbázismotor konkrét óraolvasási és COMMIT-közeli ellenőrzési
mechanizmusa nyitott technikai részlet.

### L1-D04 — Audit és operation receipt

Alapértelmezés szerint minden sikeres lifecycle commandhoz egy atomi audit
esemény és műveleti bizonylat tartozik. Ha több önállóan auditálandó esemény
kell, a commandhatárokat vagy az eseménymodellt külön meg kell tervezni.
Nem vezetünk be indokolatlanul párhuzamos operation ledgert. A receipt
azonosítása, tárolási leképezése és feloldási szerződése még véglegesítendő;
ez a döntés önmagában nem engedélyez harmadik táblát.

### L1-D05 — Auditadatok korlátozása

Zárt command-, esemény- és hibakódkészlet szükséges. Eseményenként legfeljebb
4096 UTF-8 bájt metaadat tárolható, strukturált, verziózott sémával.
Titkos token és korlátlan nyers AI- vagy üzleti adat nem menthető auditba.
A konkrét kódkatalógus, szerializálás és bájtmérés módja még nyitott.

### L1-D06 — Egyidejű lezárások

Az elsőként érvényesen véglegesített terminális állapot marad érvényben,
és nem írható felül. A sikeres lezárásnak a tranzakció véglegesítésekor is
teljesítenie kell a határidő-, jogosultsági és üzleti feltételeket.
A további ismert megszakítási körülmények naplózhatók; az elsődleges lezárási
ok egyértelmű marad. A konkurens okok pontos prioritása nincs eldöntve.

### L1-D07 — Jogosultság és belső lezárás

Az előírt végrehajtási pontokon friss jogosultságellenőrzés kell.
A felhasználó jogosultságának elvesztése után Merlin nem folytathat AI- vagy
üzleti műveletet. A rendszer szigorúan korlátozott belső jogosultsággal
biztonságosan lezárhatja és naplózhatja a futást; ez nem jogosít új
vizsgálatra vagy üzleti intézkedésre. Jogosultság-visszavonásnál a tervezett
terminális állapot `CANCELLED`; a pontos okkód és feltételrendszer a technikai
szerződésben véglegesítendő.

### L1-D08 — Vizsgálati alap érvénytelenedése

Az alap érvénytelenné válását hiteles, ellenőrizhető üzleti bizonyíték
igazolja. Bizonyított megszűnés vagy érvénytelenség esetén `CANCELLED`,
nem ellenőrizhető vagy bizonytalan üzleti állapot esetén `FAILED` szükséges.
Újabb adat vagy időbélyeg önmagában nem elegendő. A bizonyító predicate és
az evidence pontos szerződése még kidolgozandó.

### L1-D09 — Korábbi RUNNING rekordok

Nincs automatikus legacy claim vagy deadline backfill, workerátvétel vagy
újraindítás. A korábbi rekordokat meg kell őrizni. Szükség esetén külön,
ellenőrzött helyreállítási eljárás szükséges; a hiányzó execution-adat nem
tekinthető automatikusan új claim vagy lejárat bizonyítékának.

### L1-D10 — Quality Gate

Az L1 kötelező része a Merlin-tesztek integrációs kiválasztásának javítása.
Az `integration` szint tartalmazza az érintett teszteket és a Merlin alapvető
regresszióját; a kiválasztási logikát külön tesztek igazolják. Az elfogadás
tényleges tesztfuttatási bizonyítékra épül. A javítás külön azonosítható
implementációs változtatás, nem e dokumentációs feladat része.

### L1-D11 — Bizonytalan tranzakciós eredmény

Bizonyított rollback után ellenőrzött újrapróbálkozás lehetséges. Bizonytalan
COMMIT után vak automatikus ismétlés tilos: először az operation receipt
alapján kell tisztázni a helyzetet. Sikertelen tisztázáskor ellenőrzött
helyreállítás kell. Külső, költséges vagy üzleti mellékhatású művelet nem
játszható újra automatikusan bizonytalan eredmény esetén. Hiányzó receipt
egyetlen olvasása nem bizonyított rollback.

### L1-D12 — PENDING és dispatch határidők

A 180 másodperces végrehajtási határidő nem tartalmazza automatikusan a
várakozási időt. A `PENDING` és `dispatch` időkorlátok a worker-réteg tervezésekor
véglegesítendők, és éles aktiválás előtt rendezettek kell legyenek.
Ez a döntés nem választ queue TTL-t, retry/backoffot vagy pending időértéket.

### L1-D13 — Felhasználói fiók érvényessége

Merlin a KM_Production tényleges jogosultsági és felhasználói szabályait
használja. Feltételezett `is_active`, `disabled_at` vagy hasonló mezőre nem
építhet. Új fiókletiltási szabály csak igazolt alkalmazásszintű szerződésből
vezethető be. Az ADR 0018 ELV1-05 által vizsgált User modell és alapséma nem
definiál ilyen mezőt; employee státusz nem helyettesíti a user contractot.

### L1-D14 — Fizetős AI-hívások

A fizetős AI-hívások alapértelmezetten tiltva maradnak. Aktiválásuk feltétele
a költségkeret és pénzügyi korlátok, a hívásszámkorlát, költségelszámolás,
a bizonytalan hívások és retry-k biztonságos kezelése, megfelelő
jogosultságellenőrzés és külön üzemeltetői engedélyezés.
Az L1 dokumentálása vagy implementálása nem engedélyez fizetős szolgáltatást.

## 3. Függőségek és megőrzendő architektúra

| Döntések                       | Függőség és következmény                                                                                                 |
| ------------------------------ | ------------------------------------------------------------------------------------------------------------------------ |
| L1-D01, L1-D02, L1-D04, L1-D05 | A commandhatár, a receipt és az eseményséma közösen véglegesítendő a persistence előtt.                                  |
| L1-D03, L1-D06                 | A deadline és a COMMIT-kori feltételek ellenőrzése együtt bizonyítandó; a tranzakció elején vett idő önmagában nem elég. |
| L1-D06, L1-D07, L1-D08, L1-D13 | A terminalizálás jogosultsága, okai, üzleti bizonyítékai és versenyhelyzetei közös mátrixot igényelnek.                  |
| L1-D04, L1-D09, L1-D11         | A reconciliation receiptet használ, de nem ad legacy claimet vagy workerátvételt.                                        |
| L1-D03, L1-D09, L1-D12         | Az új execution deadline, a legacy rekord és a queue-várakozás eltérő szerződés; nincs implicit backfill.                |
| L1-D10                         | Minden későbbi L1 elfogadási bizonyíték megbízhatóságának kötelező kapuja.                                               |
| L1-D07, L1-D11, L1-D14         | Sem jogosultságvesztés, sem bizonytalan eredmény nem engedélyez új paid hívást.                                          |

Az ADR 0017 öt állapota és transition topologyja megmarad: `PENDING` →
`RUNNING`/`FAILED`/`CANCELLED`; `RUNNING` → `COMPLETED`/`FAILED`/`CANCELLED`;
terminális állapotból nincs további átmenet. Az L1-D01 parancsonkénti
leképezését még meg kell tervezni. Az állapotnevek itt fogalmi nevek;
a meglévő tárolási enumot e dokumentum nem módosítja.

Megőrzendő az egy aktív run/case, a 8 model-turn és 4 tool-attempt korlát,
a terminális lezárástól számított 365 napos retention és lawful hold határa,
a történeti actor provenance és a friss saját-result authorization.
Az ADR 0018 egyszeri claim/fencing, atomikus audit, friss authorization és
eligibility, valamint takeover/resume tilalma változatlan irány.
`RecoverRun` ellenőrzött helyreállítást jelent, nem végrehajtásfolytatást.

A Laravel birtokolja a lifecycle-t a Service → Repository → Model rétegzésben.
Merlin tanácsot ad; a result nem emberi jóváhagyás és nem procurement,
inventory vagy production végrehajtási jog. Nincs közvetlen AI-adatbázisírás.

## 4. Dokumentációs eltérések és nyitott kérdések

| Forrás                                               | Eltérés vagy pontosítás                                                                                                                                       | Következő szükséges munka                                                                                                                      |
| ---------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| ADR 0018 ELV1-01, 6. rész                            | Run metadata/claim/deadline mezőket vázol, de nem végleges DDL-t; L1-D02 két kiegészítő táblát rögzít a `merlin_runs` átépítése nélkül.                       | A két táblára épülő kapcsolat-, constraint- és tranzakciós terv; meglévő run/admission invariánsok megőrzése.                                  |
| ADR 0018 ELV1-06                                     | Több event/command és konkrét unique kulcs javaslata szerepel. L1-D04 alapértelmezése egy atomi esemény és receipt.                                           | Command/event cardinality és replay identity külön tervezése; a javasolt kulcsok nem automatikusan elfogadottak.                               |
| ADR 0018 OPEN-05                                     | Clock/representation és metadata bounds korábban nyitott. L1-D03 adatbázisórát és egész UTC epoch seconds értéket, L1-D05 4096 UTF-8 bájtot választ.          | SQL-kifejezés, típus/tartomány, szerializálás és kétmotoros enforcement nyitott marad.                                                         |
| ADR 0018 ELV1-02/03/04                               | Revocation mapping és okprioritás részben PROPOSED; friss ellenőrzés önmagában nem igazol race-free revocationt.                                              | L1-D07 tervezett `CANCELLED` irányának pontos feltételei/okkódja; L1-D06 COMMIT-kori garanciájának bizonyítható technikai terve.               |
| Investigation specifikáció 7.6.                      | `UNDETERMINED` → `COMPLETED / HUMAN_INPUT_REQUIRED` régi PROPOSED mapping.                                                                                    | ADR 0018 ELV1-03 és L1-D08 szerint bizonytalan authoritative state → `FAILED`; a forrás későbbi, külön engedélyezett összehangolása szükséges. |
| Investigation specifikáció 7.6.                      | `AUDIT_FAILED` → `FAILED` javaslat auditírási hiba esetére.                                                                                                   | Nem szabad nem auditált terminális sikert feltételezni; az atomi rollback, receipt és recovery hibaszerződése véglegesítendő.                  |
| ADR 0018 10. rész, OPEN-06                           | Javítás vagy explicit verified coverage alternatívát enged.                                                                                                   | L1-D10 ennél konkrétabb: a selector javítása és külön tesztjei kötelezőek, kézi suite-futtatás nem helyettesíti.                               |
| ADR 0017/specifikáció implementációs státuszmondatai | Egyes szövegek még függő commitot/review-t említenek; a kiindulási Git-történet már tartalmazza a trusted admission commitját.                                | Történeti állítást nem kezelünk aktuális review- vagy runtime-bizonyítékként.                                                                  |
| Recovery 001                                         | A dokumentum történeti checkpointja `fdbad40a0a337e80a82b96b3004a77e9cf6dbe22`, míg a most megadott kiinduló HEAD `71df227dbfed27101700bad836d30c3bf0c3011c`. | A későbbi commit maga hozta létre a Recovery 001 dokumentumot; ez igazolt történeti különbség, nem hibás branch.                               |
| Eredeti readiness/L1 audit                           | Önálló jelentés nem található a vizsgált repository-források között.                                                                                          | Eredeti bizonyíték visszakeresése vagy új, külön feladatban reprodukált audit; nincs utólag kitalált PASS.                                     |

Az elfogadott ADR-ek és a specifikáció ebben a feladatban változatlanok.
A fenti eltérések dokumentált tervezési/forrásegyeztetési feladatok;
a nyitott részletek nem válnak elfogadottá a baseline mentésétől.

## 5. Quality Gate: ismert kockázat

Az ADR 0018 10. részének megállapítását a kiinduló források olvasása is
alátámasztja: a [mátrix](../../../config/quality-gates.php) nem nevez meg
Merlin suite-ot; az [AffectedSelector](../../../app/Support/QualityGate/AffectedSelector.php)
ismeretlen source esetén `integration` fallbackot választ; a
[GatePlanner::integration](../../../app/Support/QualityGate/GatePlanner.php)
a modul- és integration-listából épít backend parancsot, nem egyesíti a
kiválasztott changed backend teszteket. Így a releváns Merlin regresszió
kimaradhat egy sikeres integration futásból.

Ez forráskód-ellenőrzés, nem új command-plan vagy tesztfutási bizonyíték.
L1-D10 alapján a javításnak külön implementációként ki kell terjednie a
kiválasztásra, az alapvető regresszióra és a selector tesztjeire; elfogadáskor
a tényleges futtatott suite-ot, környezetet és eredményt rögzíteni kell.
A hiba e mentésben nincs kijavítva, és az L1 implementáció elfogadását blokkolja.

## 6. Következő szakasz kötelező belépési és elfogadási feltételei

1. Új Git-biztonsági ellenőrzés és külön implementációs felhatalmazás.
2. Kódírás előtt végleges lifecycle technikai szerződés: hat command teljes
   transition/authority/input/output mátrixa; zárt reason/event/error készlet;
   prioritások; replay/fingerprint és receipt reconciliation.
3. Két kiegészítő tábla pontos sémája: típusok, indexek, FK/unique/CHECK,
   metadata bounds, tokenvédelem, retention és legacy rekordok kezelése;
   a `merlin_runs` szerkezetének megőrzésével.
4. MySQL/SQLite adatbázisóra és deadline-egyenlőség, lock order, tranzakciós
   tulajdonjog, audit-atomicitás, COMMIT-kori feltételek és bizonytalan
   eredmény rendezésének bizonyítható terve. A valódi mid-COMMIT connection-loss
   bizonyítékot post-commit fault injection nem helyettesíti.
5. Az authoritative érvénytelenség/bizonytalanság evidence contractja,
   a tényleges User/Gate contract és a szűk belső termination/recovery authority.
6. Külön Quality Gate javítás és tényleges selector/regressziós bizonyíték
   az L1 elfogadása előtt; a séma implementációjánál releváns migration/full
   és guardolt MySQL ellenőrzések, a projekt DoD szerint.

Worker/dispatch aktiválása előtt ezen felül szükséges a pending/dispatch
policy, recovery owner és hozzáférés, queue visibility/timeout/cleanup
összhangja és operatív bizonyítéka. A konkrét pending időérték nem e
dokumentációs mentés feltétele. A paid hívásokhoz az L1-D14 szerinti külön
költség- és üzemeltetői kapu kell. Retention cleanup, lawful deletion és
human decision workflow implementációja külön scope marad.

Javasolt következő belépési pont: **L1 technikai szerződés véglegesítése**,
majd külön azonosítható Quality Gate javítás és L1 persistence munka.
Az ADR 0018 L1 → L2 → L3 → L4 → L5 függőségi iránya megmarad; L1 nem indít
workert, nem dispatchol jobot és nem fogyaszt model/tool attemptet.

## 7. E dokumentációs feladat lezárási határa

Kizárólag ez a dokumentum és a Recovery 002 készül. Nincs alkalmazáskód-,
konfiguráció-, teszt- vagy ADR-módosítás, migráció vagy AI-szolgáltatóhívás.
A dokumentációs DoD ellenőrzése a 14 döntés teljességére, az elfogadott/nyitott
határra, a forráseltérésekre, relatív linkekre, formázásra, teljes tartalomra
és a kétfájlos Git-diffre terjed ki. Alkalmazás-, migrációs, E2E-, build-,
Pint/PHPStan- és dependency-audit kapuk itt `N/A`: nincs futási viselkedést,
függőséget vagy végrehajtható kódot érintő változás. UI-fordítás, README,
steering, playbook, API és memória módosítása nem szükséges e kétfájlos
mentéshez; a régi specifikáció összehangolása külön engedélyezendő.
A tényleges ellenőrzési eredmények és a mentés commitja a zárójelentésben szerepelnek.
