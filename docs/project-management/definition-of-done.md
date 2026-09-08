# KM_Production Definition of Done

## Cél és használat

Ez a dokumentum mondja meg, mikor tekinthető késznek egy KM_Production-feladat.
Minden emberi és AI-agent által végzett változtatásra alkalmazandó. Ez az
elsődleges szabály az ellenőrzések szükségességére, az eredmények igazolására,
az akadályok jelentésére és a lezárás feltételeire.

A feladat akkor `done`, ha az elfogadási feltételei és minden rá vonatkozó
kötelező követelmény igazoltan teljesült. A szerző feladata, hogy azonosítsa
ezeket, elvégezze az ellenőrzéseket, és tényszerűen jelentse az eredményt.

Használat:

1. Határozd meg a jóváhagyott feladatot és a változás lehetséges hatását.
2. Válaszd ki az alábbi általános és változástípus szerinti követelményeket.
3. A [rétegezett ellenőrzések útmutatójából](../development/quality-gates.md)
   válassz ellenőrzési szintet, és egészítsd ki a külön szükséges vizsgálatokkal.
4. Rögzítsd a tényleges eredményeket. A hiányzó igazolásokat rendezd a lezárás előtt.

## Arányos ellenőrzés

Minden olyan ellenőrzést el kell végezni, amely a változás kockázatára
vonatkozik. A cél a megfelelő bizonyosság: a legnagyobb tesztcsomag futtatása
önmagában nem teszi jobbá az ellenőrzést. Szélesebb műszaki hatáshoz szélesebb
vizsgálat tartozik.

Az alkalmazandó követelményeket a módosított működés, az érintett modulok és
a lehetséges hiba következménye alapján válaszd ki. A valóban nem alkalmazandó
pontot rövid `N/A` indoklással jelöld. Ez alkalmazhatósági döntés, nem egy
kihagyott kötelező ellenőrzés eredménye.

Csak dokumentációt érintő változásnál a formázás, a hivatkozások, a szóhasználat
és a whitespace-hibák vizsgálata szükséges. Ez merge előtt is így van.
Alkalmazásteszt akkor kell, ha alkalmazáskód vagy konfiguráció is változik,
vagy más alkalmazandó szabály indokolja, például futtatható vagy generált
viselkedést meghatározó dokumentáció esetén.

Több modul együttműködését érintő közös alkalmazáskódhoz Integration szintű
ellenőrzés tartozik. Projekt-, teszt-, build- és ellenőrzési infrastruktúra,
függőségkezelés vagy globális keretrendszer-beállítás változása Full kockázatú.
A részletes választási szabály és a futtató ismert eltérései a
[rétegezett útmutatóban](../development/quality-gates.md) találhatók.

A `composer qa:full` a teljes helyi rétegezett parancs. Nem foglal magában
minden projektellenőrzést: az alkalmazandó MySQL-, további böngészős,
helyesírási és dokumentációs vizsgálatok külön követelmények maradnak.

## Ellenőrzési eredmények

Egy ellenőrzés eredményét az alábbi négy értékkel kell jelenteni.

| Eredmény  | Jelentés                                                                                | Példa                                                                          |
| --------- | --------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| `PASSED`  | Az alkalmazandó ellenőrzés ténylegesen lefutott és sikeres volt.                        | A formázás ellenőrzése nem talált hibát.                                       |
| `FAILED`  | Az alkalmazandó ellenőrzés lefutott és sikertelen eredményt adott.                      | Hibás teszt, statikus elemzési hiba vagy tiltott sérülékenységet találó audit. |
| `BLOCKED` | Az ellenőrzés külső vagy környezeti előfeltétel hiánya miatt érdemben nem végezhető el. | Nem érhető el a szükséges MySQL tesztszerver.                                  |
| `NOT RUN` | Az alkalmazandó ellenőrzés nem futott le.                                               | A futtató egy korábbi hibánál leállt, vagy az ellenőrzést nem indították el.   |

A `BLOCKED` eredményhez azonosított környezeti akadály kell. A környezet
előkészítésének elmaradása önmagában `NOT RUN`. Böngészőindítási hibánál
előbb tisztázd az okot; a bizonyított környezeti indítási akadály `BLOCKED`.
Egy nehezen javítható teszthiba ettől még `FAILED` marad.

A lefutott, tiltott sérülékenységet vagy biztonsági figyelmeztetést találó
függőségaudit `FAILED`, nem `BLOCKED`. Az audit érdemi elvégzését megakadályozó
külső feltétel hiánya külön eset, és bizonyítást igényel.

A tervezési futás (`--dry-run`) nem igazol `PASSED` ellenőrzéseket. Ha az
összesített futtatás egy hibás parancsnál megáll, a sikeres korábbi lépések
`PASSED`, a hibás lépés `FAILED`, a későbbiek `NOT RUN` eredményt kapnak.
Igazolt környezeti akadálynál a sikertelen indítás technikai hibakódját is
őrizd meg, és az érintett ellenőrzést az okkal együtt `BLOCKED`-ként jelentsd.

## Az eredmény és a feladat állapota

Az ellenőrzés eredménye nem a feladat állapota. Egy sikeres parancs nem
bizonyítja az összes követelmény teljesülését. Alkalmazandó kötelező ellenőrzés
`FAILED`, `BLOCKED` vagy `NOT RUN` eredménye mellett a munka nem nevezhető
maradéktalanul késznek.

Minden ilyen ellenőrzés jelentése tartalmazza:

- az ellenőrzés nevét és eredményét;
- az okot és a hiányzó igazolás hatását;
- a javításért vagy feloldásért felelős személyt vagy szerepet;
- a következő lépést és a feloldás feltételét.

A kihagyás leírt oka nem teljesíti a követelményt. A feladat a hiány rendezéséig
nem kész, kivéve, ha külön, kifejezetten felhatalmazott szabályozási döntés
elfogadja a kockázatot. Ez a dokumentum nem ad ilyen felhatalmazást. Az esetleges
döntést külön kell igazolni; a sikertelen vagy elmaradt futás eredménye ettől
nem változik `PASSED`-re.

A backlogban kizárólag a [backlog-konvenciók](backlog-conventions.md) állapotai
használhatók: `planned`, `ready`, `in-progress`, `blocked`, `review`, `done`,
`cancelled`. Ha az akadály miatt nincs érdemi továbblépés, a feladat `blocked`;
egyébként `in-progress` vagy `review` lehet. A „részben kész” nem külön állapot.

## Fogalmak

- **Acceptance Criteria (AC):** a feladat megfigyelhető elfogadási feltételei;
  megmondják, mit kell elkészíteni.
- **Definition of Ready (DoR):** a munka indításának feltételei: érthető feladat,
  elfogadási feltételek, függőségek, kockázat és ellenőrzési igény.
- **Definition of Done (DoD):** az általános és alkalmazandó szakági
  követelmények, amelyek alapján az eredmény késznek minősíthető.
- **Review-ready:** a változás és az önellenőrzés átadható felülvizsgálatra;
  az ismert hiányok tételesen szerepelnek.
- **Merge-ready:** a felülvizsgálat lezárult, és az összes alkalmazandó
  követelmény rendezett. A részletes feltételek lent találhatók.
- **Release-ready:** a verziójelölt a merge feltételein túl a kiadás
  üzemeltetési és biztonsági követelményeit is teljesíti.

A „code complete” csak a megvalósítás elkészültét jelenti. Nem bizonyít
ellenőrzést, dokumentáltságot, felülvizsgálatot vagy kiadhatóságot.

## Minden változtatás kötelező minimuma

Az alábbi pontokat a változásra alkalmazandó körben kell teljesíteni.

### Feladat és elfogadási feltételek

- A megvalósítás a jóváhagyott feladatra korlátozódik. A további változás külön
  feladat vagy dokumentált döntés.
- Minden AC teljesül, és megfigyelhető eredménnyel igazolt.
- Nincs elhallgatott hiba, regresszió, akadály vagy félbehagyott követelmény.

### Üzleti és architekturális helyesség

- A vonatkozó domain-, ADR-, architektúra- és steering-szabályok teljesülnek.
- A gyártási nyomon követhetőség, a sorozatszámok, a műveleti sorrend verziói,
  az auditnapló, a jogosultságok és a készletmozgások alapfeltételei nem sérülhetnek.
- Alkalmazáskódnál megmarad a `Controller -> Service -> Repository -> Model`
  rétegzés; üzleti logika nem kerül controllerbe.

### Kód- és tartalomminőség

- A diff érthető és a feladatra szorítkozik. Nincs benne idegen módosítás,
  hibakereső kód, titok, helyi generált fájl vagy indokolatlanul megmaradt holt kód.
- Az alkalmazandó formázási, statikus elemzési és whitespace-ellenőrzés sikeres.
- Hiba nem fedhető el általános elnémítással, PHPStan baseline-nal vagy
  gyengített teszttel.

### Tesztelés és regresszió

- Ha alkalmazásteszt szükséges, a legszűkebb, a változást igazoló automatizált
  teszt lefut. Szélesebb hatásnál a kapcsolódó működés regresszióját is vizsgálni kell.
- A sikeres és a fontos hibás vagy jogosulatlan esetek ellenőrzöttek.
- Manuális vizsgálat csak akkor helyettesít automatizált tesztet, ha az nem
  ésszerűen automatizálható. A lépések és eredmények ekkor is rögzítettek.

### Biztonság, adat és jogosultság

- A bemenetek, jogosultságok, érzékeny adatok, naplózás, fájlkezelés és
  függőségek változással érintett részei felülvizsgáltak.
- Jogosultságváltozásnál a tiltott közvetlen hozzáférés igazoltan elutasított.
- Adatváltozásnál ismert az integritásra, tranzakciókra, az ismételt végrehajtás
  biztonságára (idempotencia) és a visszaállításra gyakorolt hatás.

### Dokumentáció és lokalizáció

- A dokumentáció a tényleges működést írja le. Az elfogadott szabálytól eltérő
  megvalósítást kifejezetten eltérésként jelöli.
- A módosított relatív hivatkozások célja létezik.
- UI-szöveg közös Laravel JSON fordítási kulcsot használ; a magyar és angol
  fordítás együtt frissül.
- A backlog, a végrehajtási terv és a kiadási megjegyzések tényszerűek.

### Git és felülvizsgálat

- Staging esetén csak a feladathoz tartozó fájlok kerülnek bele; a teljes diff
  átnézett. A commit és a PR címe követi a
  [commitüzenet-konvenciót](commit-conventions.md).
- PR készítésekor a [PR-sablon](../../.github/pull_request_template.md) és a
  [code review útmutató](code-review-guide.md) szerint kell leírni a változást,
  a kockázatot, a visszaállítást és a bizonyítékot.
- Csak ténylegesen lefutott ellenőrzés jelölhető sikeresnek.

## Megvalósítás és futtatási hivatkozások

Az alábbi parancsok meglévő projektscriptek vagy használt eszközök. A táblázat
segít megtalálni a futtatási módot; nem teszi minden sorát minden feladatra
kötelezővé. Az összesített parancsok kiválasztását a
[rétegezett útmutató](../development/quality-gates.md) írja le.

| Terület                              | Parancs                                                                                                                               | Eljárás vagy korlát                                                                              |
| ------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| Dokumentáció                         | `npx prettier --check <files>`; `git diff --check`                                                                                    | A módosított fájlokat add meg. A linkeket külön ellenőrizd; nincs dedikált linkellenőrző script. |
| Backend formázás és statikus elemzés | `vendor/bin/pint --test`; `composer analyse`; `composer validate --strict`                                                            | [Statikus elemzés](../static-analysis.md).                                                       |
| Backend teszt                        | `composer test:backend:sqlite`; `composer test:backend:mysql`                                                                         | [Backend-eljárás](../backend-quality-gate.md); MySQL csak dedikált, védett tesztadatbázison.     |
| Cache regresszió                     | `composer test:cache`                                                                                                                 | Gyorsítótárazott adatforrást módosító írásnál.                                                   |
| Migráció                             | `composer test:backend:migrations:sqlite`; `composer test:backend:migrations:mysql`                                                   | Előre migrálás, visszaállítás és seeder-ellenőrzés.                                              |
| Frontend                             | `npm run test:frontend`; `npm run i18n:check`; `npm run build`                                                                        | [Frontend-eljárás](../frontend-testing.md), a változás kockázata szerint.                        |
| E2E                                  | `npm run test:e2e`; `npm run test:e2e:a11y`; `npm run test:e2e:keyboard`; `npm run test:e2e:cross-browser`; `npm run test:e2e:mobile` | [E2E-eljárás](../e2e-testing.md), izolált környezet és telepített böngészők.                     |
| Függőségek biztonsága                | `npm audit`; `npm audit --omit=dev`; `composer audit`                                                                                 | Az audit megállapítását az alkalmazandó biztonsági szabály szerint értékeld.                     |

A GitHub Actions jobok pull requestre és `main` pushra futnak. A workflow
létezése nem bizonyítja a GitHub required státuszt. A jobnevek és a javasolt
branch-protection beállítások a [code review útmutatóban](code-review-guide.md)
találhatók; az aktuális beállítás külön igazolást igényel.

### Korábbi bizonyíték és aktuális feladat

A [backlog](backlog.md) a `CI-003` feladatot `done` állapotban tartja nyilván.
A [MySQL-audit](../audits/backend-mysql-quality-gates-2026-07-28.md) rögzíti a
helyi SQLite/MySQL, migrációs és GitHub Actions bizonyítékot. Ez korábbi futások
eredménye, nem a mostani változás sikeres ellenőrzése.

A további CI-feladatok állapotát a backlogból kell ellenőrizni. Korábbi
méréseket és hibákat a dátumozott auditok őriznek; ezeket nem szabad aktuális
sikerként vagy tartós szabályként átvenni.

## Változástípus szerinti feltételek

Több típust érintő változásnál minden alkalmazandó rész szükséges.

### 1. Dokumentáció

- A tartalom, a parancspéldák és a terminológia ellenőrzöttek; az ismert
  szabály–megvalósítás eltérések egyértelműen jelöltek.
- A módosított linkek, címsorok és kódpéldák helyesek.
- A formázás és a `git diff --check` sikeres. Alkalmazásteszt csak az
  [arányos ellenőrzés](#arányos-ellenőrzés) szabálya szerint szükséges, merge előtt is.

### 2. Backend

- A réteghatárok, FormRequest, policy, tranzakció és activity log hatása felülvizsgált.
- A változást célzott Pest/feature teszt igazolja; közös működésnél szélesebb
  regressziós vizsgálat szükséges.
- Pint és Larastan sikeres az alkalmazandó körben.

### 3. Frontend

- Az Inertia prop-, Vue prop/event- és route-szerződés összhangban marad.
- A betöltés, üres adat, siker, validációs hiba és egyéb hiba állapota kezelt
  az érintett folyamatban.
- Célzott Vitest, i18n-ellenőrzés és indokolt esetben production build futott.
  Felhasználói folyamatnál reszponzív és billentyűzetes ellenőrzés is történt.

### 4. Adatbázis-migráció

- A meglévő adatokra, indexekre, adatbázis-korlátokra, zárolásra és telepítési
  sorrendre gyakorolt hatás dokumentált.
- A visszaállítás adatvesztési és kompatibilitási hatása elemzett. Egy `down()`
  metódus létezése önmagában nem bizonyíték.
- Az alkalmazandó SQLite- és MySQL-migrációs ellenőrzés védett környezetben
  sikeres. SQLite nem helyettesíti a szükséges MySQL-igazolást.
- Elérhetetlen MySQL tesztinfrastruktúránál a MySQL-ellenőrzés `BLOCKED`, és a
  feladat nem teljesen kész.

### 5. Biztonság és jogosultság

- Engedélyezett, tiltott, eltérő szerepkörű és közvetlen route-hozzáférés tesztelt.
- A policy mellett a backend jogosultság-ellenőrzése is érvényesül; egy menü
  elrejtése nem hozzáférés-védelem.
- Az érzékeny adat, napló, cache, feltöltés, letöltés és auditnyom hatása felülvizsgált.

### 6. Refaktor

- A feladat nem tartalmaz elrejtett új funkciót vagy kompatibilitást törő változást.
- A korábbi viselkedést teszt vagy összehasonlítható bizonyíték védi.
- A publikus szerződések és domain-alapfeltételek változatlansága igazolt.

### 7. Teljesítmény

- A kiindulási és módosított állapot azonos módszerrel mért.
- Az állítást lekérdezésszám, futási idő, memória vagy más mérőszám támasztja alá.
- Az indexekre, cache-re, nagy adathalmazokra és jogosultsági elkülönítésre
  gyakorolt hatás elemzett.

### 8. Lokalizáció

- A felhasználói szöveg a közös Laravel JSON kulcsot használja.
- A magyar és angol kulcskészlet egyezik; az `npm run i18n:check` sikeres.
- A dinamikus helyettesítés, többes szám és UI-helyigény az érintett nézetben ellenőrzött.

### 9. Projekt-, CI-, teszt- vagy build-infrastruktúra

- Az ellenőrzési infrastruktúra, tesztkonfiguráció, buildkonfiguráció és
  globális keretrendszer-beállítás változása Full kockázatú.
- A módosított script, indítási feltétel, jobnév, környezet, időkorlát és
  kimeneti riport hatása dokumentált.
- Az ellenőrző eszköz változásánál sikeres és szándékosan hibás próba igazolja
  a helyes működést és a hibajelzést. Ez nem minden alkalmazásmódosítás feltétele.
- Required check csak igazolt GitHub-beállítás alapján nevezhető requirednak.

### 10. Függőségfrissítés

- A lockfile célzottan változik; a frissítés oka, verzióhatása, licenc- és
  biztonsági kockázata ismert.
- A Full mellett minden külön szükséges audit, teszt és build lefutott.
  A kompatibilitási és futtatókörnyezeti változások dokumentáltak.
- A tiltott sérülékenységet találó audit `FAILED`. A megállapítás nem
  hallgatható el; a javítás felelőse és következő lépése rögzített.
- Esetleges kockázatelfogadáshoz külön felhatalmazott döntés, felelős és
  határidő kell; egy leírt kivétel önmagában nem elegendő.

## Review-ready

A változtatás átadható felülvizsgálatra, ha a megvalósítás és az önellenőrzés
elkészült, a teljes diff, az AC-k állapota, a kockázat és a visszaállítás leírt.
Az ellenőrzések tényleges eredménye és környezete rendelkezésre áll; minden
hiány a fenti jelentési szabály szerint szerepel. Nincs elhallgatott hiba.

Hiányzó ellenőrzés mellett megkezdhető a hiány vizsgálata, de a feladat nem
`done`. Környezeti akadály mellett a felülvizsgálatra átadott munka legfeljebb
`review` állapotú lehet. Ha még érdemi javítás végezhető, `in-progress` marad;
ha nincs érdemi továbblépés, `blocked`.

## Merge-ready

A review-ready feltételeken túl:

- a teljes PR diff felülvizsgált;
- nincs nyitott `BLOCKER` vagy `REQUIRED` megállapítás;
- minden alkalmazandó AC és DoD-követelmény rendezett;
- érdemi új módosítás után az érintett felülvizsgálat és ellenőrzés megismétlődött;
- a PR-leírás, migrációs és visszaállítási tudnivalók, dokumentáció és backlog naprakész.

A merge önmagában nem teszi kötelezővé a `composer qa:full` futtatását.
Dokumentációs változás alkalmazástesztek nélkül is lehet merge-ready, ha
alkalmazásteszt nem vonatkozik rá. Nagy kockázatú infrastruktúra-változásnál
Full és a parancsból hiányzó, külön szükséges ellenőrzések is kellenek.
A merge-ready állapot még nem release-ready.

## Release-ready

A verziójelölt minden változása merge-ready, és ezen felül:

- a [release-checklist](../../.kiro/checklists/release.md) alkalmazandó pontjai teljesültek;
- ismert a migráció, jogosultság, seeder, konfiguráció, queue/scheduler és
  fájltárolás üzemeltetési hatása;
- a telepítés és visszaállítás lépései, felelősei és igazolásai rendelkezésre állnak;
- nincs feloldatlan kiadási akadály vagy rendezetlen kötelező ellenőrzés.

A sikertelen, akadályozott vagy elmaradt ellenőrzés dokumentálása nem teszi a
változást kiadhatóvá. A „kivételek dokumentálva” megjegyzés nem automatikus
elfogadás. A fenti lezárási szabály és a kiadás saját követelményei együtt érvényesek.

A kiadáshoz gyűjtsd össze tételesen a workflow-k és ellenőrzőlisták eredményeit.
Az egységes kiadási bizonyítékcsomaghoz kapcsolódó munkát a backlog `CI-009`
tétele követi.

## Bizonyíték rögzítése

A szabály azt mondja meg, minek kell teljesülnie. A script és konfiguráció
mutatja, mit hajt végre az eszköz. Az eljárás leírja az indítás lépéseit.
A bizonyíték egy konkrét végrehajtás megfigyelt eredménye.

Minden bizonyíték tartalmazza a parancsot vagy reprodukálható lépést, az
időpontot vagy futásazonosítót, a releváns környezetet és a mérhető eredményt.
A CI-link, riport, képernyőkép és napló kiegészítheti ezt, de nem helyettesíti
az eredmény értelmezését. A korábbi auditokat változatlan történeti forrásként használd.

Az alábbiak kitöltési minták, nem tényleges futási eredmények:

```text
Ellenőrzés: fordítási kulcsok egyezése
Parancs: npm run i18n:check
Időpont / futásazonosító: <tényleges érték>
Környezet: <operációs rendszer és Node-verzió>
Eredmény: PASSED
Megfigyelés: exit code 0; a magyar és angol kulcskészlet egyezik.
```

```text
Ellenőrzés: közvetlen hozzáférés jogosultság nélkül
Lépés: jogosultság nélküli felhasználó megnyitja az /admin/... route-ot.
Időpont / környezet: <tényleges értékek>
Eredmény: PASSED
Megfigyelés: 403 válasz; adat nem módosult.
```

A „minden működik”, „a tesztek rendben vannak” vagy „production ready” állítás
önmagában nem bizonyíték. Elmaradt vagy sikertelen ellenőrzésnél a név és az
eredmény mellett az ok, hatás, felelős és következő lépés is kötelező.

## Kivételek és AI-agent szabályok

- Kényelmi okból kötelező követelmény nem jelölhető `N/A`-nak.
- Hotfixnél az ellenőrzés arányossága változhat, de a kockázat, célzott
  vizsgálat, visszaállítás és utánkövető feladat nem hagyható el.
- Kivétel nem fogadhat el adatvesztést, jogosultságmegkerülést,
  titokkiszivárgást vagy hamis tesztbizonyítékot.
- Az AI-agent a feladat elején azonosítja az AC-ket, az alkalmazandó
  követelményeket és az elvárt igazolást.
- Nem bővíti önkényesen a feladatot, és explicit kérés nélkül nem módosít üzleti logikát.
- Csak ténylegesen futtatott parancsot, ellenőrzött fájlt és megfigyelt eredményt jelent.
- Hiányzó igazolás mellett nem állít teljes készültséget. Felülvizsgálatot,
  approvalt, required státuszt vagy kiadhatóságot nem állít külső igazolás nélkül.
- Átadás előtt ellenőrzi a teljes diffet, a staginget, a titkokat, a
  backlogállapotot és a módosított hivatkozásokat.
- Commitot, push-t, PR-t, merge-et vagy repository-beállítást csak az adott
  műveletre vonatkozó felhatalmazással végez.

## Újrahasználható DoD-ellenőrzőlista

- [ ] A jóváhagyott feladat és minden AC teljesült.
- [ ] A vonatkozó domain- és architektúraszabályok teljesültek.
- [ ] A diff csak a feladathoz tartozik; nincs titok, hibakereső kód vagy idegen fájl.
- [ ] Az alkalmazandó változástípus szerinti feltételek teljesültek.
- [ ] A szükséges tesztek, statikus elemzés, formázás és build sikeresek.
- [ ] A biztonsági, jogosultsági, adat- és visszaállítási hatás ellenőrzött.
- [ ] A dokumentáció, lokalizáció és relatív hivatkozások naprakészek.
- [ ] Az eredményekhez konkrét végrehajtási bizonyíték tartozik.
- [ ] A nem alkalmazandó pontok `N/A` indoka rögzített; a hiányzó ellenőrzés
      nem szerepel teljesített követelményként.
- [ ] A teljes diff, az esetleges staging, commit/PR cím és backlogállapot ellenőrzött.
- [ ] Nincs rendezetlen kötelező ellenőrzés, akadály, `BLOCKER` vagy `REQUIRED`.

## Kapcsolódó szabályok

- [Rétegezett ellenőrzések](../development/quality-gates.md)
- [Ellenőrzési lista](../../.kiro/checklists/quality-gates.md)
- [Backlog-konvenciók](backlog-conventions.md)
- [Commitüzenet-konvenció](commit-conventions.md)
- [Code review útmutató](code-review-guide.md)
- [Pull request sablon](../../.github/pull_request_template.md)
- [Commit előtti checklist](../../.kiro/checklists/before-commit.md)
- [Merge előtti checklist](../../.kiro/checklists/before-merge.md)
- [Release-checklist](../../.kiro/checklists/release.md)
- [Hozzájárulási útmutató](../../CONTRIBUTING.md)
