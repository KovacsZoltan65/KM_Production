# Merge előtti ellenőrzőlista

## Cél és hatály

Merge előtt azt igazold, hogy a változás minden alkalmazandó követelménye
rendezett. A készültség elsődleges szabálya a
[Definition of Done](../../docs/project-management/definition-of-done.md);
a felülvizsgálat menete a [code review útmutatóban](../../docs/project-management/code-review-guide.md)
található.

A merge önmagában nem ír elő `composer qa:full` futást. A DoD szerint csak
dokumentációt érintő változás alkalmazás-QA nélkül is merge-ready lehet, ha
alkalmazásellenőrzés nem vonatkozik rá. Full kockázatú változásnál a
[rétegezett útmutató](../../docs/development/quality-gates.md) szerinti Full és
a parancsból hiányzó, külön alkalmazandó ellenőrzések is szükségesek.

## Követelmények és bizonyíték

- [ ] Minden alkalmazandó elfogadási feltétel és általános, illetve
      változástípus szerinti DoD-követelmény rendezett és igazolt.
- [ ] A kiválasztott ellenőrzés megfelel a változás kockázatának; az esetleges
      konfigurációs eltérést és a `qa:full` korlátait figyelembe vettem.
- [ ] A dokumentációs formázás, linkek és szóhasználat ellenőrzött. Alkalmazásteszt
      csak alkalmazandó követelményként szerepel, nem a merge automatikus feltételeként.
- [ ] A szükséges teszt-, formázási, statikus elemzési, build- és biztonsági
      eredmények konkrét parancshoz vagy eljáráshoz és környezethez kapcsolódnak.
- [ ] Az alkalmazandó MySQL- és migrációs igazolás rendelkezésre áll; nem
      helyettesítettem SQLite-eredménnyel.
- [ ] A DoD szerinti `FAILED`, `BLOCKED` és `NOT RUN` eredményeket tételesen
      felülvizsgáltam. Nincs rendezetlen kötelező ellenőrzés; az indoklás vagy
      review-approval önmagában nem oldotta fel a hiányt.
- [ ] Esetleges kockázatelfogadáshoz a DoD szerinti külön felhatalmazott döntés
      igazolható. Egy leírt kivételt nem tekintettem automatikus merge-engedélynek.

## Felülvizsgálat és átadás

- [ ] A teljes PR diff felülvizsgált, és csak a jóváhagyott feladathoz tartozik.
- [ ] Nincs nyitott `BLOCKER` vagy `REQUIRED` megállapítás; a javításokat és
      tisztázásokat a felülvizsgáló ellenőrizte.
- [ ] Új érdemi módosítás után az érintett review és jóváhagyás megismétlődött;
      a szükséges új ellenőrzési eredmények rendelkezésre állnak.
- [ ] Az érintett üzleti, regressziós, teljesítmény-, biztonsági, jogosultsági,
      API-, adatbázis- és lokalizációs hatás felülvizsgált.
- [ ] A kompatibilitást törő változás, migráció, permission/seeder, telepítés és
      visszaállítás tudnivalói dokumentáltak, ahol alkalmazandók.
- [ ] A PR címe követi a [commitkonvenciót](../../docs/project-management/commit-conventions.md);
      a PR-leírás, dokumentáció és backlog tényszerű és naprakész.
- [ ] Nincs titok, idegen változás, szükségtelen helyi fájl vagy elhallgatott hiba.
- [ ] A tényleges GitHub-védelmek teljesülése ellenőrzött; required státuszt nem
      következtettem pusztán workflow-fájl létezéséből.
- [ ] AI-agentként az [AGENTS.md](../../AGENTS.md) szerinti, a merge-re vonatkozó
      explicit felhatalmazás rendelkezésre áll. Ez a lista nem ad felhatalmazást.

Rendezetlen kötelező követelménynél a hiányt és a következő lépést jelentsd,
ne merge-készséget. A sikeres merge-előkészítés a kiadás üzemeltetési
feltételeit még nem igazolja; a release-ready állapotot is a DoD alapján kell értékelni.
