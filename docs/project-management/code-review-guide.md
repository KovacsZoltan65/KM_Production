# Code review útmutató

## Cél

Ez a dokumentum a KM_Production pull request és code review folyamatának
elsődleges útmutatója. A review célja az üzleti helyesség, biztonság,
traceability és karbantarthatóság igazolása, nem pusztán a formázás ellenőrzése.

A PR szerzője a
[pull request sablont](../../.github/pull_request_template.md) használja. A PR
céljának lezárását a
[projektszintű Definition of Done](definition-of-done.md) alapján bizonyítja.
A PR
címe a [commitüzenet-konvenció](commit-conventions.md) tárgysor-formátumát
követi:

```text
<type>(<scope>): <subject>
```

A cím angol, opcionális kebab-case scope-ot használ, nem végződik ponttal,
ideális esetben legfeljebb 72, abszolút legfeljebb 100 karakter. Nem lehet
branchelv, fájllista, puszta backlog ID vagy `Merge ...` szöveg. Squash merge
esetén várhatóan ez lesz a végleges commitcím, ezért release-minőségűnek kell
lennie.

## Jelenlegi repository-állapot

A 2026-07-28-i audit szerint:

- egy általános PR-sablon került bevezetésre;
- issue-sablon, `CODEOWNERS` és Dependabot-konfiguráció nincs;
- a backend és frontend GitHub Actions workflow pull requestre fut;
- a repository-fájlokból nem igazolható branch protection, required check,
  minimum approval, conversation resolution vagy merge-stratégia beállítása;
- a dokumentumban szereplő védelmi és required-check szabályok javaslatok,
  nem automatikusan végrehajtott GitHub-beállítások.

`CODEOWNERS` csak valódi, jóváhagyott GitHub-felhasználó vagy csapat
azonosítójával hozható létre. Jelenleg nincs ehhez megbízható tulajdonosi adat.

## A szerző feladata

- Fókuszált, logikailag összetartozó PR készítése.
- A backlog- vagy issue-kapcsolat megadása, ha létezik.
- A teljes branch változásának, nem csak az utolsó commitnak a leírása.
- A technikai döntések, kockázatok, migráció és rollback tényszerű rögzítése.
- Csak ténylegesen futtatott parancs és mérhető eredmény felsorolása.
- A nem futtatott releváns ellenőrzések indoklása.
- A reviewer fókuszának és a breaking change-nek egyértelmű jelölése.
- Új érdemi módosítás után review vagy jóváhagyás újbóli kérése.

## A reviewer feladata

- A PR céljának és elfogadási feltételeinek megértése.
- A brancheltérés és a tesztbizonyíték érdemi vizsgálata.
- Az üzleti, biztonsági és adatkonzisztencia-kockázatok előrevétele.
- A megjegyzések súlyosságának egyértelmű jelölése.
- Annak ellenőrzése, hogy minden `BLOCKER` és `REQUIRED` lezárult-e.
- Új érdemi commit után az érintett részek újraellenőrzése.

## Review sorrend

1. A PR célja, scope-ja és backlogkapcsolata.
2. Üzleti működés és elfogadási feltételek.
3. Biztonság és jogosultság.
4. Adatbázis-, migrációs és tranzakciós hatások.
5. Backend szerkezet és Laravel-konvenciók.
6. Frontend szerződések és Vue/Inertia működés.
7. Tesztlefedettség és regressziós kockázat.
8. Teljesítmény és query viselkedés.
9. Lokalizáció és dokumentáció.
10. Kódolvashatóság és karbantarthatóság.
11. Merge-kész állapot.

A review ne formázási észrevétellel kezdődjön, ha üzleti vagy biztonsági hiba
előbb tisztázandó.

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

- A `defineProps()` és Inertia propok szerződése egyezik.
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

## Tesztek és regresszió

Nem minden PR-re kell minden teszt. A szerző a változás kockázatához illő
parancsokat futtatja és pontosan felsorolja.

| Változás                      | Jellemző minimum                                                  |
| ----------------------------- | ----------------------------------------------------------------- |
| Csak dokumentáció             | Prettier, relatív hivatkozások, `git diff --check`                |
| Backend üzleti logika         | Célzott Pest/feature teszt, Pint, Larastan                        |
| Széles vagy kritikus backend  | Teljes releváns backend suite                                     |
| Frontend komponens vagy oldal | Célzott Vitest, Prettier, i18n check, indokolt esetben build      |
| Közös frontend komponens      | Teljes frontend tesztkészlet és production build                  |
| Adatbázis-migráció            | Migrate/rollback, kapcsolódó feature teszt, MySQL és SQLite hatás |
| Jogosultság vagy biztonság    | Engedélyezett, tiltott, szerepkör- és közvetlen route-eset        |
| Refaktor                      | A változatlan viselkedést igazoló regressziós teszt               |
| Teljesítmény                  | Előtte-utána mérés vagy query count                               |

Homályos „tests passed” vagy „everything works” nem tesztbizonyíték.

## Dokumentáció és lokalizáció

- A felhasználói szövegek közös Laravel JSON translation key-t használnak.
- A magyar és angol fordítás együtt frissül.
- A dokumentáció a tényleges viselkedést, migrációt és üzemeltetési hatást írja
  le.
- A backlogállapot csak teljesült elfogadási és validációs feltételekkel változik.
- Breaking change és rollback információ nem marad csak review-kommentben.

## Teljesítmény

- Query count, eager loading, index és cache hatás értékelt.
- Teljesítményjavítási állításhoz összehasonlítható mérés tartozik.
- Nagy adathalmazon végzett migráció vagy riport kockázata dokumentált.
- A cache felhasználó- vagy jogosultságfüggő adata nem szivároghat.

## Review megjegyzések súlyossága

| Jelölés      | Jelentés                                                                                                                  |
| ------------ | ------------------------------------------------------------------------------------------------------------------------- |
| `BLOCKER`    | Nem merge-elhető: adatvesztés, jogosultságmegkerülés, hibás üzleti működés, törött build, kritikus regresszió vagy titok. |
| `REQUIRED`   | Merge előtt javítandó: lényeges teszt, validáció, edge case, szerződés vagy dokumentáció hiánya.                          |
| `SUGGESTION` | Hasznos fejlesztési javaslat, amely önmagában nem blokkol.                                                                |
| `QUESTION`   | Tisztázó kérdés vagy döntési indok kérése.                                                                                |
| `NIT`        | Apró stílus- vagy elnevezési észrevétel; valódi kockázat nélkül nem blokkol.                                              |
| `PRAISE`     | Jó döntés vagy megoldás kiemelése.                                                                                        |

Példák:

```text
BLOCKER: This authorization check can be bypassed by changing the route ID.
REQUIRED: Add a regression test for concurrent stock reservation.
SUGGESTION: Consider extracting this status mapping into the existing enum.
QUESTION: Is this query expected to include inactive locations?
NIT: This variable name could be more specific.
PRAISE: The transaction boundary keeps the stock movement atomic.
```

A governance-kommunikáció magyar; kód, azonosítók és közvetlen kódpéldák
angolul maradhatnak.

## Jóváhagyási szabályok

- A szerző ne hagyja jóvá saját PR-ját.
- Több aktív közreműködő esetén legalább egy érdemi reviewer ajánlott.
- Kritikus területhez domainismerettel rendelkező reviewer szükséges.
- Nyitott vagy megválaszolatlan `BLOCKER` vagy `REQUIRED` mellett nincs merge.
- Új érdemi commit után az érintett review és jóváhagyás megismétlendő.
- Nagy kockázatú változásnál a puszta „looks good” nem elegendő.
- Dokumentációs PR-nél arányos review használható.
- Sürgős javításnál a kockázat, kivétel és utólagos ellenőrzés dokumentálandó.

Ezek projektfolyamat-szabályok. A GitHubon kikényszerített minimum approval
jelenleg nem igazolt.

## Újraellenőrzés

A szerző minden megjegyzésre javítással, indoklással vagy tisztázással válaszol.
A reviewer az új diffet és az új tesztbizonyítékot ellenőrzi. Elavult
jóváhagyás nem tekinthető automatikusan érvényesnek, ha a változás érdemi része
módosult.

## Merge stratégia

Alapértelmezett javaslat a GitHub squash merge. Egy PR így egy logikai
Conventional Commitként jelenik meg, a PR címe pedig támogatja a changelog és
release note készítését.

Squash merge feltételei:

- a PR cím megfelel a commitkonvenciónak;
- a PR leírás és backlogállapot naprakész;
- nincs nyitott `BLOCKER` vagy `REQUIRED`;
- a releváns checkek sikeresek;
- breaking change, migráció és rollback dokumentált;
- a szükséges review megtörtént.

Merge commit csak több önállóan értékes commit történetének, release- vagy
hosszú életű integrációs ág megőrzésére, dokumentált döntéssel indokolt.

Rebase merge nem alapértelmezett. Csak akkor indokolt, ha minden commit önálló,
konvenciókövető és WIP/javító köztes committól mentes. Megosztott történet
átírása vagy automatikus force push nem megengedett.

A GitHub merge-stratégiát ez a dokumentum nem módosítja.

## Tényleges és javasolt required checkek

Az alábbi jobok pull requestre futnak, de repository-szintű required státuszuk
nem igazolt:

| Ellenőrzés                           | Workflow               | Job neve                                          | Javasolt required | Feltétel vagy backlogkapcsolat                                  |
| ------------------------------------ | ---------------------- | ------------------------------------------------- | ----------------- | --------------------------------------------------------------- |
| Composer validáció, Pint és Larastan | `Backend quality gate` | `Backend Static Analysis`                         | Igen              | A check kontextusát `CI-005` alatt GitHubon igazolni kell.      |
| SQLite backend és cache regresszió   | `Backend quality gate` | `Backend Tests / SQLite`                          | Igen              | Stabil PR-futás után.                                           |
| MySQL backend tesztek                | `Backend quality gate` | `Backend Tests / MySQL`                           | Igen              | A `CI-003` zöld helyi és Actions bizonyítéka rendelkezésre áll. |
| MySQL migráció és seeder smoke       | `Backend quality gate` | `Database Migrations / MySQL`                     | Igen              | A `CI-003` zöld round-trip és Actions bizonyítéka rendelkezésre áll. |
| Frontend unit tesztek                | `Frontend`             | `Frontend Unit Tests`                             | Még nem           | A stabil checkkontextust `CI-005` alatt GitHubon igazolni kell. |
| Frontend lokalizáció                 | `Frontend`             | `Frontend i18n Check`                             | Még nem           | A stabil checkkontextust `CI-005` alatt GitHubon igazolni kell. |
| Frontend production build            | `Frontend`             | `Frontend Production Build`                       | Még nem           | A stabil checkkontextust `CI-005` alatt GitHubon igazolni kell. |
| npm dependency audit                 | `Frontend`             | `Frontend Dependency Audit`                       | Még nem           | A finding- és kivételpolicy a `CI-007` feladata.                |
| Chromium, accessibility és keyboard  | `Frontend`             | `Playwright Chromium, accessibility and keyboard` | Még nem           | `CI-004` stabilitási auditja után.                              |
| Cross-browser és mobile smoke        | `Frontend`             | `Playwright cross-browser and mobile smoke`       | Még nem           | `CI-004` után; költség- és flakység-review szükséges.           |
| Prettier                             | Nincs                  | Nincs                                             | Nem               | Előbb ellenőrizhető workflow vagy projekt-script szükséges.     |
| Composer security audit              | Nincs                  | Nincs                                             | Nem               | `CI-006`.                                                       |

A frontend unit, i18n és production build jobok stabil checkkontextust adnak,
de required státuszuk nincs repository-beállításból igazolva. Az npm security
audit külön check, policy-ja továbbra is a `CI-007` feladata. A backend
workflow `git diff --check` lépésének PR-patch lefedettsége külön
igazolandó, mert tiszta checkout mellett önmagában nem bizonyítja a teljes PR
whitespace-állapotát. A required státuszok beállítása a `CI-005` és `GOV-005`
feladata, nem ennek a dokumentációnak a végrehajtott változása.

## Branch protection javaslat

A `main` ághoz javasolt adminisztrátori checklist:

- [ ] Pull request szükséges merge előtt.
- [ ] Legalább egy approval szükséges, ha több aktív közreműködő van.
- [ ] Új commit esetén a stale approval eldobódik.
- [ ] Minden review conversation feloldása kötelező.
- [ ] Csak a `CI-005` alatt igazolt, stabil jobnevek required checkek.
- [ ] Az up-to-date branch követelmény csak stabil CI mellett aktív.
- [ ] Force push és branch deletion tiltott.
- [ ] Admin bypass külön, dokumentált döntés.
- [ ] Linear history csak az engedélyezett merge-stratégiával összhangban.
- [ ] Signed commit nem kötelező külön biztonsági döntés nélkül.
- [ ] Auto-merge csak külön jóváhagyott folyamatban engedélyezhető.

## Merge előtti ellenőrzés

Merge előtt:

- a [projektszintű Definition of Done](definition-of-done.md) releváns
  feltételei teljesültek;
- a [merge előtti ellenőrzőlista](../../.kiro/checklists/before-merge.md)
  releváns pontjai teljesültek;
- a teljes PR diff review-zott;
- a teszteredmény konkrét és naprakész;
- minden kötelező review-megjegyzés lezárt;
- a PR címéből érvényes squash commitcím készül;
- a dokumentáció, backlog, migráció és rollback tényszerű;
- nincs titok, lokális fájl vagy elhallgatott ismert hiba.

## AI által készített változások

Az AI-agent:

- csak explicit engedéllyel hozhat létre vagy frissíthet PR-t;
- nem állíthatja, hogy review vagy approval történt, ha nem történt;
- nem jelöl be nem igazolt checkboxot;
- csak ténylegesen futtatott parancsot és eredményt ír le;
- külön jelzi a nem futtatott releváns ellenőrzéseket;
- nem rejt el ismert hibát, és nem használ hosszú munkanaplót összefoglalásként;
- nem kér merge-et nyitott `BLOCKER` vagy `REQUIRED` mellett;
- nem módosít branch protectiont, required checket vagy merge-stratégiát;
- nem engedélyez auto-merge-et, nem amendel, nem rebase-el és nem force-pushol
  külön engedély nélkül;
- nem publikál személyes adatot vagy lokális abszolút elérési utat;
- a teljes brancheltérést ellenőrzi:

```bash
git status --short
git log --oneline origin/main..HEAD
git diff --stat origin/main...HEAD
git diff --name-status origin/main...HEAD
git diff origin/main...HEAD
```

A PR leírása a teljes branch tartalmát írja le, és nem jelöl teljesítettnek
igazolatlan elfogadási feltételt.
