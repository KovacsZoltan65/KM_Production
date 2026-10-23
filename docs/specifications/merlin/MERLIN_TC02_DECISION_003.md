# Merlin L1 — TC-02 döntési checkpoint 003

- **Dátum:** 2026-10-23.
- **Döntés:** ACCEPTED — TC-02 / 1. döntés.
- **A TC-02 egészének státusza:** BLOCKED.
- **Hatókör:** a felhasználó által jóváhagyott architekturális döntés dokumentálása; kizárólag dokumentáció, implementációs engedély nélkül.
- **Ellenőrzött Git-kiindulás:** `feature/merlin-candidate`, `00e017729bc6f454dc35fe9bfbe149110168bd80`; a helyi és a `git ls-remote` szerinti távoli branch HEAD-je azonos, a staging üres, kizárólag a Technical Contract DRAFT untracked.

## 1. Cél és források

A `FinalizeRun` a Merlin-vizsgálat sikeres terminális lezárását végzi;
nem jelent üzleti végrehajtást vagy a Problem Case megoldását. Ez a checkpoint
kizárólag a sikeres lezárás 180 másodperces határidőfeltételének döntési
időpontját rögzíti. A konkrét végrehajtási protokoll továbbra is nyitott.

Kapcsolódó források:

- [L1 döntési alapállapot](MERLIN_L1_DECISION_BASELINE.md): különösen `L1-D03`, `L1-D04`, `L1-D06`, `L1-D07` és `L1-D11`.
- [L1 Technical Contract DRAFT](MERLIN_L1_TECHNICAL_CONTRACT_DRAFT.md): különösen a `FinalizeRun`, az 5.2. rész garanciahatára és a TC-02 kapu.
- [Recovery Checkpoint 002](MERLIN_RECOVERY_002.md): a korábbi döntések és a folytatás határai.
- [ADR 0017 — Investigation Foundation](../../../.kiro/decisions/0017-merlin-investigation-foundation.md): authorization, lifecycle és végrehajtási limitek.
- [ADR 0018 — Execution Lifecycle](../../../.kiro/decisions/0018-merlin-execution-lifecycle.md): egyszeri claim, immutable deadline, friss ellenőrzés, atomi audit és bizonytalan COMMIT kezelése.

A DRAFT 5.2. része különbséget tesz a tranzakción belüli utolsó ellenőrzés
és a fizikai COMMIT időpontja között. A kettő közötti várakozás miatt az
előbbi önmagában nem igazolja a COMMIT-kori határidőt. Ez a jóváhagyott döntés
az időkövetelmény e részét módosítja; nem bizonyít működő SQL-, zárolási vagy
verziókezelési protokollt, és nem zárja le a TC-02 egészét.

## 2. ACCEPTED — TC-02 / 1. döntés

A sikeres FinalizeRun 180 másodperces határidőfeltételét egy pontosan
meghatározott, szerializált, tranzakción belüli döntési ponton kell teljesíteni.

A fizikai COMMIT később is bekövetkezhet.

A döntési pont DB-órát használ, és a határidő összehasonlítása szigorú:

`db_now < execution_deadline_epoch`

Egyenlőségnél a FinalizeRun nem engedélyezett.

A döntési pont konkrét SQL-, lock- és version-protokollja még nincs jóváhagyva.
A szerializált döntési pont követelmény; pontos műszaki definíciója és
bizonyítása nyitott. A fizikai COMMIT későbbi időpontjának megengedése
önmagában nem garantál sikeres lezárást: a többi szükséges feltétel és az
atomi tranzakció követelménye változatlanul teljesítendő.

## 3. Változatlan garanciák

- A claimhez rendelt végrehajtási időkeret 180 másodperc; a sikeres claimkor meghatározott deadline immutable, nem hosszabbítható meg.
- A szükséges jogosultsági és üzleti feltételek COMMIT-ig tartó védelme kötelező.
- Az állapotváltozás és az audit/receipt atomikus: közösen commitolnak vagy rollbackolnak.
- A terminális állapot immutable; a későbbi eredmény nem írhatja felül.
- Unknown COMMIT esetén nincs vak retry; előbb az eredeti művelet kimenetét kell tisztázni. A receipt hiánya önmagában nem bizonyított rollback.
- Jogosultságvesztés nem adhat új végrehajtási jogot; a szűk belső lezárási vagy reconciliation authority nem jogosít új AI- vagy üzleti műveletre.

## 4. Viszony a korábbi döntésekhez és dokumentációs eltérés

Az [L1-D06](MERLIN_L1_DECISION_BASELINE.md#l1-d06--egyidejű-lezárások)
korábbi megfogalmazása a sikeres lezárás határidő-, jogosultsági és üzleti
feltételeinek teljesülését egyaránt a tranzakció véglegesítéséhez, azaz a
fizikai COMMIT-hoz köti. **A jelen ACCEPTED döntés ennek a fizikai COMMIT-kori
időkövetelményét pontosítja/módosítja:** a határidőnek a szerializált,
tranzakción belüli döntési ponton kell teljesülnie; a fizikai COMMIT
bekövetkezhet a deadline után is. A jogosultsági és üzleti védelem továbbra
is COMMIT-ig kötelező.

Az `L1-D03` DB-óra-, 180 másodperces, immutable deadline- és szigorú
összehasonlítási elve megmarad. Az ADR 0017/0018 lifecycle-, authorization-,
terminális immutability- és auditgaranciáit ez a döntés nem oldja fel.

A korábbi baseline, a Technical Contract DRAFT és az ADR-ek ebben a
checkpointban változatlanok. A DRAFT 5.2. részének és TC-02 sorának fizikai
COMMIT-deadline megfogalmazása ezért még a korábbi követelményt tükrözi.
Ez kifejezetten dokumentált eltérés, nem hallgatólagos forrásátírás és nem
az eredeti COMMIT-kori időkövetelmény teljesítésének bizonyítéka.

Későbbi, külön engedélyezett dokumentációs összehangolás szükséges:
az `L1-D06` és kapcsolódó baseline-szövegek, a DRAFT érintett command-,
tranzakciós, tesztterv- és kapuszövegei, valamint az ADR-ek kapcsolódó
hivatkozásai és garanciahatárai e döntéssel visszakövethetően egyeztetendők.
A nyitott technikai részletek ezen összehangolás során sem jelölhetők
elfogadottnak külön döntés vagy bizonyíték nélkül.

## 5. OPEN / BLOCKED — nyitott követelmények

- A linearization point pontos műszaki definíciója, beleértve a konkrét SQL-, lock- és version-protokollt.
- A teljes permission graph frissessége és zárolása.
- A Spatie permission-cache kezelésének bizonyítása.
- A material shortage netting teljes üzleti függőségi körének védelme.
- A konkurens üzleti writerek és phantom beszúrások kezelése.
- A MySQL/SQLite protokollok célzott tesztelése.
- A DB-óra és clock rollback szabályai.
- Az unknown COMMIT reconciliation bizonyítása.
- A hiányzó `km_production_test` tesztadatbázis rendezése.

**A TC-02 egészének státusza továbbra is BLOCKED.** Az elfogadott időszemantika
nem igazolja a COMMIT-ig tartó permission/business védelmet, a cache
frissességét, a konkurenciakezelést vagy a reconciliation helyességét.
A tesztadatbázis hiánya rögzített nyitott előfeltétel; ebben a dokumentációs
feladatban nincs új DB-ellenőrzés, adatbázis-létrehozás vagy adatot módosító
parancs. Korábbi audit- vagy tesztállítás nem új futtatási bizonyíték.

## 6. Következő mérföldkő

`MERLIN_TC02_AUTHORIZATION_BUSINESS_GUARD_CONTRACT_DRAFT`

Cél: a COMMIT-ig érvényes jogosultsági és üzleti védelmi protokoll kidolgozása,
külön implementáció nélkül.

Javasolt hatókör: a teljes permission graph, cache és üzleti függőségi kör
olvasási/írási útvonalainak feltárása; a konkurens writerek és phantomok
védelmi szerződése; a döntési pont, lock/version sorrend és érvényességi
feltételek kétmotoros terve; DB-óra/clock rollback és unknown COMMIT
reconciliation szerződés; célzott tesztterv és a tesztkörnyezet hiányzó
előfeltételeinek rögzítése. A javasolt protokollok külön jóváhagyandó
tervezetek maradnak; e mérföldkő nem implementáció és nem adatbázis-rendezés.

## 7. Ellenőrzési és átadási határ

Kizárólag ez az új döntési dokumentum készül. A DRAFT, a baseline, az ADR-ek,
az alkalmazáskód, migrációk, tesztek és konfiguráció változatlanok.
Nincs staging, commit, push, adatbázis-módosítás vagy implementáció.

A [projekt Definition of Done](../../project-management/definition-of-done.md)
dokumentációs változástípusa alkalmazandó: teljes tartalmi önellenőrzés,
magyar nyelv, az ACCEPTED döntés és az OPEN / BLOCKED követelmények
elkülönítése, forráskapcsolatok, relatív linkek, Prettier, whitespace és
Git-hatókör ellenőrzése. A tényleges eredmények a zárójelentésben szerepelnek.
Alkalmazás-, migrációs, build-, E2E- és adatbázistesztek itt `N/A`:
nincs végrehajtható változás; a protokollok tesztelése nyitott követelmény.
Más dokumentációs réteg és UI-fordítás frissítése nem része ennek az
egyfájlos checkpointnak; a korábbi források összehangolása külön munka.
