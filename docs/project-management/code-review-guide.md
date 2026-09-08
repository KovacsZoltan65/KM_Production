# Code review útmutató

## Cél és szabályforrások

A felülvizsgálat azt vizsgálja, hogy a változás a jóváhagyott feladatot oldja-e
meg, megőrzi-e az üzleti működés helyességét és biztonságát, és elegendő
bizonyíték támasztja-e alá. A teljes PR-t kell értékelni, nem csak az utolsó commitot.

Ez az útmutató a szerző és a felülvizsgáló teendőit írja le. A
[Definition of Done](definition-of-done.md) marad a készültség, alkalmazhatóság,
eredményállapotok és hiányzó ellenőrzések elsődleges szabálya. Az ellenőrzési
szinteket és a futtató korlátait a [rétegezett útmutató](../development/quality-gates.md)
határozza meg. Ezeket a review során alkalmazzuk, nem külön szabályként újradefiniáljuk.

A commit egy szándékos munkapont rögzítése lehet; nem bizonyítja a DoD
szerinti teljes készültséget. A review megkezdése és a review-approval sem
helyettesít hiányzó ellenőrzést. A merge feltételeit külön kell igazolni.

## A szerző előkészítése

1. Írd le a konkrét problémát és a változás utáni működést. A PR egy logikailag
   összetartozó feladatot tartalmazzon; add meg a backlog- vagy issue-kapcsolatot, ha van.
2. A [PR-sablon](../../.github/pull_request_template.md) szerint foglald össze
   a teljes ág változását, a lényeges döntéseket, kockázatokat és visszaállítást.
3. Azonosítsd az alkalmazandó DoD-követelményeket, válassz ellenőrzést a
   rétegezett útmutatóból, és add meg a konkrét eredményeket.
4. Emeld ki a felülvizsgáló figyelmét igénylő döntéseket, a kompatibilitást
   törő változásokat és a még nyitott követelményeket.
5. Új érdemi módosítás után kérd az érintett részek újbóli felülvizsgálatát
   és a jóváhagyás megismétlését, a műveletre vonatkozó felhatalmazás keretében.

A PR címe a [commitüzenet-konvenciót](commit-conventions.md) követi:

```text
<type>(<scope>): <subject>
```

A cím angol, a scope opcionális és kebab-case alakú. Ideális hossza legfeljebb
72, abszolút maximuma 100 karakter; nincs a végén pont. A változást írja le,
ne ágnevet, fájllistát, puszta backlogazonosítót vagy `Merge ...` szöveget.
Squash merge esetén ebből készülhet a végleges commitcím.

## Mit ellenőriz a felülvizsgáló?

Először a célt és az elfogadási feltételeket értsd meg. Ezután az üzleti,
biztonsági és adatkonzisztencia-kockázatokat vizsgáld; a formázás ezek után
következik. A változásra alkalmazandó területeken haladj:

1. Üzleti működés, nyomon követhetőség és jóváhagyott feladat.
2. Biztonság, jogosultság, adatbázis, migráció és tranzakció.
3. Backend rétegek, frontend működés, API- és Inertia-szerződések.
4. Tesztek, regressziós kockázat és tényleges ellenőrzési eredmények.
5. Teljesítmény, lokalizáció, dokumentáció és olvashatóság.
6. Nyitott megállapítások, jóváhagyhatóság és merge-feltételek.

Jelöld az észrevételek súlyosságát, és ellenőrizd a kötelező javítások eredményét.
Új érdemi commit után az érintett részeket és bizonyítékot ismét vizsgáld meg.

## Üzleti helyesség

- A változás megfelel a backlog scope-jának és elfogadási feltételeinek.
- A gyártási traceability, serial number és operation sequence verzió megmarad.
- Készletmennyiség csak stock movement folyamaton keresztül változik.
- Státuszátmenet, kerekítés, időpont és mennyiség üzletileg helyes.
- Versenyhelyzet, idempotencia és ismételt kérés hatása értékelt.
- Auditnapló és visszakövethetőség indokolt eseménynél rendelkezésre áll.

## Biztonság és jogosultság

Szükség szerint ellenőrizendő:

- backend authorization minden érintett végponton;
- policy, permission, middleware és közvetlen route-hozzáférés;
- mass assignment és bemeneti validáció;
- SQL injection, XSS, CSRF és session viselkedés;
- fájlfeltöltési MIME-, méret- és elérésiút-korlát;
- dokumentumletöltési jogosultság;
- érzékeny mezők response-ban, logban vagy cache-ben;
- környezeti változó, titok és token;
- admin és super-admin kivételek;
- jogosultságseedelés és auditnapló.

Magas kockázatú biztonsági változás érdemi reviewer nélkül nem merge-kész.

## Adatbázis és tranzakciók

Migrációs PR-ben dokumentálandó:

- a séma és a meglévő adatok változása;
- nullable/default döntés, index, foreign key és constraint;
- nagy táblán végzett művelet futási és zárolási kockázata;
- alkalmazáskód és migráció deployment-sorrendje;
- kézi adatjavítás vagy üzemeltetési lépés;
- visszagörgethetőség és az esetleges adatvesztés.

A rollback nem csak egy `down()` metódus létezését jelenti. Vizsgálni kell,
hogy végrehajtható-e adatvesztés nélkül, vagy a veszteség dokumentált és
elfogadott-e. Breaking adatbázis-változás nem rejthető `refactor` vagy `chore`
típus alá.

A kockázat leírása önmagában itt sem elfogadás. A
[DoD kivételszabályai](definition-of-done.md) és az adatvesztésre vonatkozó
korlátai érvényesek; a reviewer nem adhat ezek alól hallgatólagos felmentést.

Több összefüggő írásnál tranzakció, megfelelő zárolás és hibatűrés szükséges.
Az időzóna-, dátum-, pénzügyi, készlet- és termelési mennyiségek pontossága
megőrzendő.

## Backend ellenőrzési szempontok

- A controller nem tartalmaz üzleti logikát.
- A service, repository és model felelőssége következetes.
- A FormRequest validáció és route model binding helyes.
- A policy és authorization backend oldalon érvényesül.
- A repository interfész és implementáció szinkronban van.
- Az exception kezelés nem nyeli el a hibát.
- Az enumok és státuszátmenetek következetesek.
- A PHPDoc valós típust ír le, a Larastan típusinformáció megmarad.
- Nincs indokolatlan N+1 query.
- A cache invalidation és auditnaplózás megfelelő.

## Frontend ellenőrzési szempontok

- A `defineProps()` és Inertia tulajdonságok szerződése egyezik.
- A `defineEmits()` események és prop/event átnevezések következetesek.
- Loading, empty, success, validation és error állapotok kezeltek.
- A PrimeVue komponensek és mezőtípusok következetesek.
- A route helper és permission viselkedés helyes.
- Hardcoded UI-szöveg helyett lokalizációs kulcs szerepel.
- A magyar és angol fordítás szinkronban van.
- A reszponzív elrendezés, címkék és billentyűzetes használat nem romlik.
- Nincs szükségtelen watcher vagy újrarenderelés.
- A kritikus viselkedést frontend teszt fedi.
- A production build lefut, ha a változás ezt indokolja.

## API és Inertia szerződések

- A response, pagination, filter és prop formátuma konzisztens.
- A frontend által elvárt mezők rendelkezésre állnak.
- A hibaválasz kezelhető és nem szivárogtat érzékeny adatot.
- Publikus route vagy API inkompatibilis változása breaking change-ként
  dokumentált.

## Dokumentáció, lokalizáció és teljesítmény

- A felhasználói szövegek közös Laravel JSON fordítási kulcsot használnak;
  a magyar és angol fordítás együtt frissül.
- A dokumentáció a tényleges működést és üzemeltetési hatást írja le. Az ismert
  szabály–megvalósítás eltérést kifejezetten jelöli.
- A backlogállapot a tényleges előrehaladást követi; hiányzó követelmény mellett
  nem állít készültséget. A kompatibilitási és visszaállítási tudnivaló nem
  maradhat kizárólag review-megjegyzésben.
- A lekérdezésszám, eager loading, indexek és cache hatása értékelt.
  Teljesítményjavítási állításhoz összehasonlítható mérés tartozik.
- Nagy adathalmazon végzett migráció vagy riport kockázata dokumentált;
  a cache felhasználó- és jogosultságfüggő adatai nem szivároghatnak.

## Milyen bizonyíték kell a review-hoz?

A DoD alapján minden alkalmazandó ellenőrzés eredménye legyen visszakereshető.
A PR-sablon „Tesztelés és ellenőrzés” részében add meg:

| Adat                   | Mit vár a felülvizsgáló?                                                                              |
| ---------------------- | ----------------------------------------------------------------------------------------------------- |
| Ellenőrzés és eredmény | A vizsgálat neve és a DoD szerinti `PASSED`, `FAILED`, `BLOCKED` vagy `NOT RUN`.                      |
| Végrehajtás            | A tényleges parancs vagy eljárás, időpont vagy futásazonosító, környezet és megfigyelt eredmény.      |
| Ok                     | Nem `PASSED` eredménynél a hiba, akadály vagy elmaradás konkrét oka.                                  |
| Hatás                  | Mit igazol a vizsgálat, illetve milyen működés maradt igazolatlan?                                    |
| Felelősség             | Ki vagy mely szerep felel az ellenőrzésért, javításért vagy feloldásért?                              |
| Következő lépés        | A hiány rendezésének lépése és feltétele; sikeres ellenőrzésnél jelezhető, hogy nincs további teendő. |

Sikeres, azonos környezetben végzett egyszerű ellenőrzések közös felelőssel és
közös környezeti leírással is felsorolhatók. A nem sikeres ellenőrzések hiányait
külön nevezd meg. Nem alkalmazandó ellenőrzésekhez nem kell részletes
hibaűrlap: a DoD szerinti rövid `N/A` indok elegendő.

A `NOT RUN` parancsot tervezett vagy elmaradt ellenőrzésként tüntesd fel, ne a
futtatott parancsok között. A még nem teljesült PR-checkbox maradjon üres.
Egy leírt ok nem teszi a követelményt teljesítetté.

A [DoD eredményszabályait](definition-of-done.md) alkalmazva a reviewer
ellenőrzi, hogy a szerző nem nevezett-e egy hibás tesztet környezeti akadálynak,
nem jelölt-e kihagyott lépést sikeresnek, és nem állított-e teljes futást
tervezési vagy korán leállt parancs alapján. A lefutott, tiltott sérülékenységet
találó audit `FAILED`; az igazolt környezeti akadály `BLOCKED`; az elmaradt
ellenőrzés `NOT RUN`. Ezek egyike sem `PASSED`.

A bizonyíték terjedelme a változáshoz igazodjon. Dokumentációs PR-nél a
módosított fájlok formázási, hivatkozási és tartalmi ellenőrzése elegendő lehet
a DoD szerint. Ne kérj automatikusan alkalmazás-QA-t. Backend-, frontend-,
migrációs vagy infrastruktúra-változásnál a megfelelő DoD-részt és a rétegezett
útmutatót használd; ez a dokumentum nem ad második tesztminimum-mátrixot.

Ha MySQL-ellenőrzés alkalmazandó, annak külön bizonyítéka kell. SQLite vagy
`composer qa:full` sikere nem helyettesíti azt. A `qa:full` teljes helyi
rétegezett parancs, nem minden projektellenőrzés összessége; a külön szükséges
vizsgálatokat a rétegezett útmutató korlátai alapján ellenőrizd.

A korábbi audit, egy sikeres job neve vagy a „minden működik” mondat nem
bizonyítja a jelenlegi PR helyességét. A review-approval sem változtatja meg az
ellenőrzési eredményt, és nem pótolja az elmaradt validálást.

## Review-megállapítások

| Jelölés      | Használat                                                                                                                            |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------------ |
| `BLOCKER`    | Merge-et megakadályozó hiba: adatvesztés, jogosultságmegkerülés, hibás üzleti működés, törött build, kritikus regresszió vagy titok. |
| `REQUIRED`   | Merge előtt rendezendő lényeges hiány: teszt, validáció, szélső eset, szerződés vagy dokumentáció.                                   |
| `SUGGESTION` | Hasznos javaslat, amely önmagában nem akadályozza a merge-et.                                                                        |
| `QUESTION`   | Tisztázandó kérdés vagy döntési indok.                                                                                               |
| `NIT`        | Apró stílus- vagy elnevezési észrevétel; valódi kockázat nélkül nem akadály.                                                         |
| `PRAISE`     | Jó döntés vagy megoldás kiemelése.                                                                                                   |

A `BLOCKER` review-megállapítás nem a DoD `BLOCKED` ellenőrzési eredménye.
A megállapítás a javítás fontosságát, az ellenőrzési eredmény a vizsgálat
kimenetét jelöli.

Példák:

```text
BLOCKER: A route ID módosításával megkerülhető a jogosultság-ellenőrzés.
REQUIRED: Hiányzik az egyidejű készletfoglalás regressziós tesztje.
SUGGESTION: A státuszleképezés beilleszthető a meglévő enumba.
QUESTION: A lekérdezésnek az inaktív helyeket is tartalmaznia kell?
NIT: A változónév pontosítható.
PRAISE: A tranzakcióhatár együtt tartja a készletmozgás írásait.
```

A szerző minden megállapításra javítással, indoklással vagy tisztázással
válaszol. A reviewer ellenőrzi az új diffet és bizonyítékot. Egy beszélgetés
lezárása önmagában nem bizonyítja a hiba megszűnését.

## Mi akadályozza a jóváhagyást?

Nyitott `BLOCKER` vagy `REQUIRED`, tisztázatlan lényeges kockázat vagy az
értékeléshez hiányzó szükséges bizonyíték mellett ne adj lezáró jóváhagyást.
A hiány feltárására és részterületek ellenőrzésére a review folytatható a DoD
review-ready szabálya szerint; ettől a változás még nem merge-ready.

- A szerző ne hagyja jóvá saját PR-ját.
- Több aktív közreműködő esetén legalább egy érdemi felülvizsgáló ajánlott.
- Kritikus területhez domainismerettel rendelkező felülvizsgáló szükséges;
  magas kockázatú biztonsági változás érdemi review nélkül nem merge-kész.
- Új érdemi módosítás után az érintett review és jóváhagyás megismétlendő.
- Dokumentációs PR-nél arányos review alkalmazandó. Nagy kockázatnál a puszta
  „looks good” nem elegendő.
- Sürgős javításnál a kockázat, visszaállítás és utánkövetés a DoD szerint
  dokumentálandó; a sürgősség nem automatikus kockázatelfogadás.

Ezek a projekt felülvizsgálati szabályai. Nem bizonyítják, hogy a GitHubon
minimum approval vagy más automatikus védelem van beállítva.

## Mi akadályozza a merge-et?

A [merge előtti listát](../../.kiro/checklists/before-merge.md) a DoD
merge-ready feltételeivel együtt alkalmazd. Merge előtt szükséges:

- minden alkalmazandó AC és DoD-követelmény rendezése;
- a teljes PR diff érdemi felülvizsgálata;
- minden `BLOCKER` és `REQUIRED` rendezése és a javítás ellenőrzése;
- az érdemi új módosítás utáni szükséges review és vizsgálatok;
- naprakész PR-leírás, cím, backlog, dokumentáció, migrációs és
  visszaállítási tudnivalók;
- titkok, idegen fájlok és elhallgatott hibák kizárása;
- a ténylegesen beállított GitHub-védelmek teljesülése.

Rendezetlen, alkalmazandó kötelező ellenőrzés mellett nincs merge-készség.
A `FAILED`, `BLOCKED` vagy `NOT RUN` eredmény leírása és a review-approval
nem oldja fel ezt. Kockázatelfogadás csak a DoD szerinti külön felhatalmazott
döntéssel igazolható; ezt az útmutató nem adja meg.

A merge ténye nem jelent automatikus `composer qa:full` kötelezettséget.
Dokumentációs változás alkalmazás-QA nélkül is lehet merge-ready, ha nincs
rá alkalmazandó alkalmazásellenőrzés. Full kockázatú változásnál a rétegezett
útmutató szerinti Full és a parancson kívüli alkalmazandó ellenőrzések is
szükségesek. A merge-ready állapot nem igazolja a kiadás üzemeltetési feltételeit.

## Merge-stratégia

Az alapértelmezett javaslat a GitHub squash merge: egy PR egy logikai
Conventional Commitként jelenik meg. Ehhez a címnek követnie kell a
commitkonvenciót, és minden fenti merge-feltételnek teljesülnie kell.

Merge commit akkor indokolt dokumentált döntéssel, ha több önállóan értékes
commit, kiadási ág vagy hosszú életű integrációs ág történetét kell megőrizni.
Rebase merge nem alapértelmezett; csak önálló, konvenciókövető, köztes
WIP/javító commitoktól mentes történetnél indokolt. Megosztott történet
átírására vagy automatikus force push-ra ez nem ad engedélyt.

A dokumentált javaslat nem módosítja a GitHub merge-beállításait.

## Jelenlegi CI-leltár – 2026-09-08-i fájlvizsgálat

Ez működési leltár, nem állandó review-követelmény és nem sikeres CI-futási
bizonyíték. Forrásai a [backend workflow](../../.github/workflows/backend-quality.yml)
és a [frontend workflow](../../.github/workflows/frontend.yml).
Mindkettő pull requestre és `main` pushra indul, dokumentációs útvonalszűrés nélkül.

| Workflow               | Job pontos neve                             | A fájlban meghatározott ellenőrzés                                                |
| ---------------------- | ------------------------------------------- | --------------------------------------------------------------------------------- |
| `Backend quality gate` | `Backend Static Analysis`                   | Composer-validálás, Pint, Larastan és `git diff --check`.                         |
| `Backend quality gate` | `Backend Tests / SQLite`                    | Cache-regresszió, teljes SQLite-tesztcsomag, SQLite-migráció és seeder-vizsgálat. |
| `Backend quality gate` | `Backend Tests / MySQL`                     | Kapcsolatpróba és teljes MySQL-tesztcsomag.                                       |
| `Backend quality gate` | `Database Migrations / MySQL`               | Kapcsolatpróba, MySQL-migráció és seeder-vizsgálat.                               |
| `Frontend`             | `Frontend Unit Tests`                       | `npm run test:frontend`.                                                          |
| `Frontend`             | `Frontend i18n Check`                       | `npm run i18n:check`.                                                             |
| `Frontend`             | `Frontend Production Build`                 | `npm run build`.                                                                  |
| `Frontend`             | `Frontend Dependency Audit`                 | `npm audit`, majd `npm audit --omit=dev`.                                         |
| `Frontend`             | `Playwright E2E`                            | Előkészítés és build után `npx playwright test --project=chromium`.               |
| `Frontend`             | `Playwright cross-browser and mobile smoke` | Előkészítés és build után Firefox, WebKit és mobile Chromium projektek.           |

A jobok külön indulnak; nem használnak `needs` függőséget vagy
`continue-on-error` beállítást. Egy jobon belül a korábbi hiba miatt későbbi
ellenőrzés elmaradhat. Például a teljes npm audit hibája után a production
audit nem feltétlenül fut le. A jelentésben a tényleges végrehajtást kell követni.

A [package.json](../../package.json) már tartalmaz `npm run format:check`
scriptet. Ez a felsorolt JavaScript/Vue fájlokat és a `package.json` fájlt
ellenőrzi; Markdown nincs a listában. A vizsgált workflow-kban nincs Prettier-lépés.
A dokumentáció formázásának parancsát a DoD-ból válaszd ki.

A [composer.json](../../composer.json) és a [futtató](../../tools/quality-gate.php)
biztosítja a helyi `qa:*` parancsokat. A helyi Full része a `composer audit`,
de a vizsgált CI-workflow-kban nincs Composer security audit lépés.
A runner nem helyettesíti automatikusan ezeket a külön CI-jobokat.

A backend workflow egyszerű `git diff --check` lépése tiszta checkout mellett
nem igazolja a teljes PR-patch whitespace-állapotát. A reviewernek a megfelelő
alaphoz tartozó diffet és annak ellenőrzését kell vizsgálnia.

Dokumentációs PR-re a jelenlegi CI miatt alkalmazástesztek is indulhatnak.
Ez nem módosítja a DoD alkalmazhatósági szabályát, és nem ad engedélyt a
GitHubon ténylegesen előírt ellenőrzés megkerülésére.

### Mi nem állapítható meg a repository-fájlokból?

A workflow neve és tartalma nem igazolja a required-check státuszt, a branch
protectiont, a minimum approvalt, a beszélgetések kötelező lezárását vagy az
engedélyezett merge-stratégiákat. Ezekhez a tényleges GitHub-beállítások külön
ellenőrzése szükséges. A leltár egyik sora sem állít ilyen beállítást.

A `.github/` jelenlegi leltára általános PR-sablont és a két workflow-t
mutatja; issue-sablon, `CODEOWNERS` és Dependabot-konfiguráció nincs benne.
`CODEOWNERS` csak jóváhagyott, valódi GitHub-felhasználóval vagy csapattal
hozható létre; tulajdonost nem szabad kitalálni.

### Korábbi javaslatok és bizonyítékok

A korábbi útmutató javaslata a négy backend checket és a Chromium E2E-t
jelölte required-check jelöltnek. A frontend unit, i18n, build, npm audit és
további böngészős ellenőrzésekhez még további igazolást vagy döntést kért.
Ez korábbi javaslat, nem végrehajtott GitHub-beállítás.

Az aktuális feladatállapotot a [backlog](backlog.md) tartja nyilván, többek
között a `CI-004`, `CI-005`, `CI-006`, `CI-007` és `GOV-005` munkáknál.
A korábbi MySQL-igazolás a
[CI-003 auditjában](../audits/backend-mysql-quality-gates-2026-07-28.md)
olvasható. Egy régi sikeres futás nem a jelenlegi PR bizonyítéka.

## Branch protection – külön jóváhagyandó javaslat

Az alábbi lista adminisztrátori döntés előkészítése a `main` ághoz. Nem
ellenőrzött aktuális konfiguráció, és nem ad felhatalmazást módosításra.

- [ ] Pull request szükséges merge előtt.
- [ ] Több aktív közreműködőnél legalább egy approval szükséges.
- [ ] Új commit után az elavult approval érvényét veszti.
- [ ] Minden review-beszélgetés lezárása szükséges.
- [ ] Csak külön igazolt, stabil ellenőrzésnevek kerülnek required státuszba.
- [ ] Az alapággal naprakész branch követelménye csak stabil CI mellett aktív.
- [ ] Force push és ágtörlés tiltott; adminisztrátori megkerülés külön dokumentált döntés.
- [ ] A lineáris történet követelménye összhangban van a merge-stratégiával.
- [ ] Aláírt commit csak külön biztonsági döntés alapján kötelező.
- [ ] Auto-merge csak külön jóváhagyott folyamatban engedélyezhető.

## AI-agent felhatalmazás és jelentés

A műveleti felhatalmazás forrása az [AGENTS.md](../../AGENTS.md) és a
[commitkonvenció](commit-conventions.md). Az AI-agent commitot, push-t, PR
létrehozását vagy frissítését és merge-et csak a szükséges explicit
felhatalmazással végezhet. Üzleti logikát nem módosíthat a jóváhagyott feladaton
kívül. A review vagy egy kitöltött lista nem ad új jogosultságot.

Repository-védelem, required check, merge-stratégia, auto-merge vagy
Git-történet módosítását sem engedélyezi ez az útmutató; ezekre az elsődleges
szabályforrások és a külön felhatalmazás vonatkoznak.

A jelentés csak megfigyelt tényeket tartalmazzon. Ne állíts megtörtént review-t
vagy approvalt igazolás nélkül, ne jelölj igazolatlan checkboxot, és ne kérj
merge-et nyitott `BLOCKER` vagy `REQUIRED` mellett. Ne publikálj személyes
adatot vagy helyi abszolút elérési utat. A PR-leírás a végső változást és
bizonyítékát magyarázza, ne hosszú munkanapló legyen.

A teljes ágeltérés vizsgálatához az alábbi parancsok használhatók, ha
`origin/main` a PR tényleges, rendelkezésre álló és megfelelően friss alapja.
Más célág esetén azt add meg:

```bash
git status --short
git log --oneline origin/main..HEAD
git diff --stat origin/main...HEAD
git diff --name-status origin/main...HEAD
git diff --check origin/main...HEAD
git diff origin/main...HEAD
```

A még nem commitolt változásokat külön is vizsgáld meg. A szerző az
[előzetes commitlistával](../../.kiro/checklists/before-commit.md) az adott
munkapontot készíti elő; a teljes feladat és a merge készültségét továbbra is a
DoD szerint kell igazolnia.
