# Commitüzenet-konvenció

## Cél

Ez a dokumentum a KM_Production commitüzeneteinek elsődleges szabályforrása.
Célja az olvasható, visszakereshető Git-történet, valamint a changelog, release
note és pull request folyamat támogatása emberi fejlesztők és AI-agentek számára.
A szabályok a jövőbeni commitokra vonatkoznak; a meglévő történetet nem kell
átírni.

## Alapformátum

A tárgysor alapformátuma:

```text
<type>(<scope>): <subject>
```

A scope opcionális:

```text
<type>: <subject>
```

Hosszabb indoklás esetén:

```text
<type>(<scope>): <subject>

<body>

<footer>
```

## Engedélyezett típusok

| Típus      | Használat                                                        |
| ---------- | ---------------------------------------------------------------- |
| `feat`     | Felhasználó vagy üzleti folyamat számára érzékelhető új funkció. |
| `fix`      | Hibás működés javítása.                                          |
| `docs`     | Kizárólag dokumentációs változás.                                |
| `test`     | Teszt vagy tesztinfrastruktúra létrehozása, javítása.            |
| `refactor` | Belső szerkezeti módosítás üzleti viselkedés változása nélkül.   |
| `perf`     | Mérhető vagy indokolt teljesítményjavítás.                       |
| `ci`       | CI/CD workflow vagy quality gate módosítása.                     |
| `build`    | Buildrendszer, függőség vagy csomagolás módosítása.              |
| `chore`    | Más típusba nem illő projektkarbantartás.                        |
| `style`    | Kizárólag formázás, működés- vagy UI-változás nélkül.            |
| `revert`   | Egy korábbi commit visszavonása.                                 |

A `style` nem vizuális UI-változást jelent. Felhasználói megjelenést érintő
változás rendszerint `feat`, `fix` vagy `refactor`.

## Scope használata

A scope opcionális, de ajánlott, ha a változás egyértelmű modulhoz vagy
technikai területhez tartozik. Kisbetűs, rövid, stabil, kebab-case alakú legyen.
Ne legyen fájlnév, osztálynév, az általános `app`, vagy indokolatlanul részletes.

Ajánlott scope-ok:

```text
auth, users, roles, master-data, items, bom, operations, orders, production,
capacity, inventory, procurement, quality, documents, reports, intelligence,
learning-center, ocr, frontend, backend, database, api, i18n, ci, git, docs,
security, performance
```

A lista nem zárt. Új, tartós projektterülethez új scope használható.

```text
fix(stock-reservations): prevent duplicate release
```

Kerülendő:

```text
fix(stockreservationservice): prevent duplicate release
```

## Tárgysor szabályai

A tárgysor:

- angol nyelvű;
- kisbetűs típussal, kettősponttal és egy szóközzel kezdődik;
- tömör felszólító vagy jelen idejű szerkezetet használ;
- önmagában érthető, és a tényleges változást írja le;
- nem végződik ponttal;
- ideális esetben legfeljebb 72, legfeljebb 100 karakter;
- nem tartalmaz hosszú fájllistát vagy munkanaplót;
- nem tesz nem ellenőrzött állítást.

Tiltott homályos tárgyak például: `update files`, `fix`, `changes`, `work`,
`WIP`, `final`, `misc`, `mentés` és `workspace`.

## Body és footer

Body szükséges, ha a tárgysor nem magyarázza el megfelelően az okot, a korábbi
viselkedést, a kompromisszumot, vagy a migrációs és üzemeltetési hatást.

- Angol nyelvű, és üres sor választja el a tárgysortól.
- Főként a változás okát magyarázza, nem ismétli meg a tárgysort.
- Lehetőség szerint legfeljebb 100 karakteres sorokat használ.

```text
fix(inventory): prevent duplicate stock reservations

Lock the stock balance row before calculating available quantity.
This prevents concurrent requests from reserving the same stock.
```

A footer használható issue-, backlog-, co-author-, revert- és breaking change
információhoz:

```text
Refs: CI-001
Closes: #123
Co-authored-by: Example Name <example@example.com>
```

A backlog ID ajánlott, ha a munkának van központi backlogeleme, de addig nem
kötelező, amíg nincs minden munkához egységes issue- vagy backlogkapcsolat.
Érzékeny adat commitüzenetben sem szerepelhet.

## Breaking change

Breaking change többek között a publikus API vagy integrációs szerződés
inkompatibilis módosítása, publikus route vagy prop átnevezése, támogatott
funkció megszüntetése, illetve manuális migrációt igénylő változás.

Jelölése:

```text
feat(api)!: replace legacy stock movement endpoint
```

vagy footerben:

```text
BREAKING CHANGE: the legacy stock movement endpoint has been removed
```

A body kötelezően leírja, mi változott, kit érint, milyen migráció szükséges,
és van-e kompatibilitási útvonal.

## Atomi commitok

Egy commit egy logikailag összetartozó változást tartalmazzon. Legyen
értelmezhető, lehetőség szerint buildelhető és tesztelhető, valamint önállóan
visszavonható.

Ne kerüljön egy commitba egymástól független feature és formázás, dependency
update és üzleti funkció, több külön hibajavítás, vagy ágtakarítás és
alkalmazáskód.

Együtt tartható az implementáció a közvetlen tesztjeivel, lokalizációjával,
migrációjával, modellváltozásával és közvetlen dokumentációjával.

## Merge és squash

- Jól felépített feature branch squash merge-dzsel egyesíthető.
- A squash commit címe kövesse ezt a konvenciót.
- Több önállóan értékes commit esetén rebase merge vagy merge commit csak
  explicit, tudatos review-döntéssel használható, megosztott történet helyi
  átírása nélkül.
- Automatikus, tartalmatlan merge cím nem release-minőségű commitüzenet.
- GitHub merge-stratégiát csak repository-adminisztrátori döntéssel szabad
  módosítani.

Jó squash cím:

```text
feat(learning-center): add course administration
```

Kerülendő:

```text
Merge pull request #42 from feature/learning-center-admin
```

## AI-agent szabályok

Az AI-agent:

- csak explicit felhasználói engedéllyel commitol és pushol;
- célzott staginget használ, és nem futtatja vakon a `git add .` vagy
  `git add -A` parancsot;
- nem commitol idegen vagy korábban meglévő módosítást;
- commit előtt ellenőrzi a staged fájlokat, a teljes diffet és a whitespace-t;
- csak ténylegesen lefuttatott tesztről vagy ellenőrzésről tesz állítást;
- kérés nélkül nem amendel, nem ír át Git-történetet és nem használ force push-t;
- branchet csak külön biztonsági ellenőrzés után töröl;
- a bodyban nem helyettesíti a projekt dokumentációját hosszú munkanaplóval;
- nem tesz érzékeny adatot a tárgysorba, bodyba vagy footerbe.

## Jó példák

```text
feat(inventory): add stock reservation release action
fix(capacity): prevent overlapping employee reservations
docs: add central project backlog
test(frontend): cover inventory dashboard cards
refactor(procurement): extract order status transitions
perf(reports): cache inventory summary queries
ci(frontend): run Vitest in stable worker mode
build(vite): adjust production chunk configuration
chore(git): clean merged branches
style: apply Pint formatting
```

Revert esetén:

```text
revert: feat(inventory): add stock transfer workflow
```

A body nevezze meg a visszavont commit SHA-ját és a visszavonás okát.

## Rossz példák

```text
Fixed some things.
Update backlog.md and next-actions.md
mentés
workspace
WIP
fix
```

Ezek nem adják meg egyértelműen a változás típusát, scope-ját vagy eredményét,
illetve fájlneveket vagy munkafázist írnak le a változás célja helyett.

## Commit előtti ellenőrzőlista

Commit előtt futtatandó:

```bash
git status --short
git diff --cached --name-status
git diff --cached --stat
git diff --cached --check
git diff --cached
```

Ellenőrizni kell, hogy:

- csak a feladathoz tartozó fájlok vannak staged állapotban;
- nincs titok, debugkód vagy idegen módosítás;
- a típus, scope és tárgy megfelel ennek a dokumentumnak;
- az állított tesztek és eredmények ténylegesen igazoltak;
- a commit logikailag atomi.

A további projekt quality gate-eket a
`.kiro/checklists/before-commit.md` tartalmazza.

## Automatizált ellenőrzés

A 2026-07-28-i auditkor a projektben nem volt commitlint, Husky, Lefthook,
lint-staged, commit-msg hook vagy GitHub Actions commitüzenet-validáció. A
`package.json` és a `composer.json` sem tartalmaz ilyen scriptet.

Ebben a fázisban nem kerül be új csomag vagy helyi hook. Ez elkerüli a
függőségnövekedést, a Windows-kompatibilitási kockázatot és a fejlesztői
folyamat idő előtti blokkolását.

Javasolt bevezetési sorrend:

1. dokumentált konvenció és manuális review;
2. PR-cím vagy commitüzenet CI-validáció a `GOV-005` és `CI-005` döntéseivel
   összehangolva;
3. helyi commit-msg hook csak külön jóváhagyással.

Az automatizálásnak a 100 karakteres abszolút maximumot, az engedélyezett
típusokat, az opcionális kebab-case scope-ot és a breaking change jelölést kell
ellenőriznie. A 72 karakter ajánlás, ezért önmagában ne blokkolja a commitot.
