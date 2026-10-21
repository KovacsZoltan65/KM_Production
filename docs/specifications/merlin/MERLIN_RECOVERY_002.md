# KM_Production — Merlin Recovery Checkpoint 002

- **Dátum:** 2026-10-21.
- **Azonosító:** `MERLIN_RECOVERY_002`.
- **Cél:** a 14 elfogadott L1 döntés és a biztonságos későbbi folytatás helyi,
  Gitből visszakereshető dokumentációs checkpointja.

## 1. Kiindulási referencia és a mentés hatóköre

Repository: `KM_Production`, helyi gyökér:
`C:\wamp64\www\km\KM_Production`.

Elvárt branch: `feature/merlin-candidate`.

Ellenőrzött kiinduló HEAD és helyi távoli követőág
(`origin/feature/merlin-candidate`):
`71df227dbfed27101700bad836d30c3bf0c3011c`.

A létrehozás előtti munkakönyvtár és staging tiszta volt; a referencia
commitként elérhető, és a megadott referenciához képest nem volt eltérés.
A távoli követőág helyi referencia: ez nem friss szerveroldali ellenőrzés,
fetch vagy push nem része a feladatnak.

A [Recovery 001](MERLIN_RECOVERY_001.md) belső történeti checkpointja
`fdbad40a0a337e80a82b96b3004a77e9cf6dbe22`. A kiinduló `71df227…` commit
ennek közvetlen utódja, és a Recovery 001 dokumentumot adta hozzá.
Ez a dokumentált történeti eltérés nem váratlan új fejlesztés.

A mentés kizárólag két fájlt tartalmazhat:

- [MERLIN_L1_DECISION_BASELINE.md](MERLIN_L1_DECISION_BASELINE.md): döntések,
  függőségek, ADR/specifikációs eltérések, nyitott részletek és kapuk.
- [MERLIN_RECOVERY_002.md](MERLIN_RECOVERY_002.md): ez a helyreállítási útmutató.

Nincs implementáció, migráció, adatbázis-művelet, AI-hívás vagy ADR-státuszváltás.
A jelen kérés felhatalmazást ad e két dokumentum helyi commitjára; pushra nem.

## 2. Rögzített döntések és nyitott feladatok

A [döntési alapállapotban](MERLIN_L1_DECISION_BASELINE.md) rögzített azonosítók:
`L1-D01`, `L1-D02`, `L1-D03`, `L1-D04`, `L1-D05`, `L1-D06`, `L1-D07`,
`L1-D08`, `L1-D09`, `L1-D10`, `L1-D11`, `L1-D12`, `L1-D13`, `L1-D14`.

Nyitott: pontos command/transition/authority contract, reason- és eseménykészlet,
receipt/replay/reconciliation, több esemény szükségessége, két kiegészítő tábla
sémája és korlátai, kétmotoros óra és COMMIT-kori ellenőrzés, konkurens
lezárási prioritás, üzleti evidence predicate, legacy helyreállítási eljárás.
A pending/dispatch időértékek és az operatív worker/recovery policy későbbi
aktiválási kapuk; a paid költség- és engedélyezési kapuk nyitottak.

Dokumentációs eltérések maradnak: ADR 0018 többeseményes javaslata és a
run metadata elhelyezése; az investigation specifikáció 7.6. régi
`UNDETERMINED → COMPLETED / HUMAN_INPUT_REQUIRED` mappingje; az auditírási hiba
utáni lezárás pontos szerződése; történeti implementációs státuszmondatok.
A részletes összevetés a baseline 4. részében van; a forrásokat nem írtuk át.
Az eredeti readiness/L1 audit önálló jelentése nem volt azonosítható a
repositoryban; az ADR hivatkozása nem pótolja az eredeti bizonyítékot.

A Quality Gate ismert Merlin-kiválasztási hiányossága megmarad.
Az L1-D10 szerint külön javítás, selector tesztek és tényleges regressziós
futtatási bizonyíték szükséges az L1 implementáció elfogadásához.
E checkpoint sem lifecycle tesztsikert, sem worker readiness-t nem igazol.

## 3. Biztonságos folytatás: először csak diagnosztika

Olvasd el az `AGENTS.md`, `README.md`, `.kiro/index.md` szerinti releváns
útmutatókat, a két checkpointot, a baseline-t, az ADR 0017/0018 és az
investigation specifikáció aktuális változatát.

Első ellenőrzések PowerShellből:

```powershell
git rev-parse --show-toplevel
git status --short --branch
git branch --show-current
git rev-parse HEAD
git rev-parse origin/feature/merlin-candidate
git diff --check
git diff --cached --check
git diff --cached --name-status
git cat-file -t 71df227dbfed27101700bad836d30c3bf0c3011c
git log --oneline --decorate -8
```

Ezután azonosítsd a dokumentációs commitot a következő rész szerint, és
hasonlítsd hozzá a HEAD-et, a munkakönyvtárat és a helyi követőágat.
Újabb commitot ne tekints automatikusan hibának; vizsgáld a teljes eltérést.

**STOP:** rossz branch, nem tiszta munkakönyvtár/staging, hiányzó szükséges
forrás vagy commit, nem egyértelmű Git-állapot, illetve váratlan commit/eltérés
esetén kizárólag diagnosztikai jelentés készülhet. Nincs automatikus javítás.
Eltérő HEAD esetén az eltérés kivizsgálása után felhasználói jóváhagyás kell
a folytatáshoz. A dokumentum olvasása nem felhatalmazás implementációra.

## 4. A végső commit és a mentési állapot ellenőrzése

Ez a fájl nem tartalmaz előre kitalált saját commitazonosítót.
A mentő commit a fájlt hozzáadó Git-bejegyzésből azonosítható:

```powershell
git log --diff-filter=A --format="%H %s" -- docs/specifications/merlin/MERLIN_RECOVERY_002.md
```

A kapott bejegyzést ellenőrizd. Több találat esetén ne válassz automatikusan;
vizsgáld meg a történetet. Az ellenőrzött teljes hash-t helyettesítsd be az
alábbi, olvasási célú parancsokban a `<checkpoint-commit>` helyére:

```powershell
git cat-file -t <checkpoint-commit>
git show --format=fuller --stat <checkpoint-commit>
git diff-tree --no-commit-id --name-status -r <checkpoint-commit>
git show <checkpoint-commit> -- docs/specifications/merlin/MERLIN_L1_DECISION_BASELINE.md docs/specifications/merlin/MERLIN_RECOVERY_002.md
git rev-parse <checkpoint-commit>^
git merge-base HEAD <checkpoint-commit>
git log --oneline <checkpoint-commit>..HEAD
git diff --stat <checkpoint-commit> HEAD
git rev-list --left-right --count HEAD...origin/feature/merlin-candidate
```

Elvárt mentő commitüzenet:
`docs: establish Merlin L1 decision baseline and recovery checkpoint 002`.
Elvárt szülő: `71df227dbfed27101700bad836d30c3bf0c3011c`.
Pontosan a két felsorolt dokumentum hozzáadása várható. A teljes tartalmat,
ne csak a fájlneveket vizsgáld meg.

A helyi mentés igazolása: a commit létezik, a megfelelő branchen elérhető,
a szülő és a teljes diff egyezik, a munkakönyvtár és a staging tiszta.
A helyi követőághoz viszonyított ahead/behind állapot nem bizonyít friss
szerveroldali állapotot vagy push-t. A végső hash és a tényleges mentési
eredmény a commit utáni zárójelentésből is ellenőrizhető; a dokumentum
puszta létezése önmagában nem igazolja a mentés sikerét.

## 5. Tiltott automatikus helyreállítás

Tilos a `git reset`, `git clean`, `git rebase`, `git merge`, `git stash`,
`git cherry-pick`, másik ágra `git checkout`/`git switch`, a helyi munkát
felülíró restore és a `git push --force`. Ne törölj vagy írj felül helyi munkát.
Ne fetch-elj, commitolj vagy pusholj új, külön felhatalmazás nélkül a későbbi
helyreállítás során. Az eredeti feladat helyi dokumentációs commitengedélye
nem általános jövőbeli Git-engedély.

Tilos adatbázist módosítani, migrációt/seedert futtatni, legacy claimet vagy
deadline backfillt készíteni, workert átvenni vagy újraindítani, paid AI-hívást
indítani, illetve ADR-státuszt megváltoztatni. Bizonytalan COMMIT vagy külső
hívás után nincs vak automatikus replay: előbb receipt alapú tisztázás,
szükség esetén ellenőrzött helyreállítás következik.

## 6. Következő szakasz belépési feltételei

Külön implementációs felhatalmazás és tiszta, értelmezett Git-kiindulás kell.
Első lépés az L1 technikai szerződés véglegesítése a baseline 6. része szerint;
ezt követheti a külön Quality Gate javítás és a két kiegészítő tábla persistence
munkája, megfelelő SQLite/MySQL és projekt DoD bizonyítékkal.

Sem az L1 dokumentálása, sem az L1 implementáció nem engedélyez önmagában
worker/dispatch aktiválást vagy paid inference-t. A pending, queue, recovery,
connection-loss és üzemeltetési kapuk a megfelelő későbbi szelet előtt
teljesítendők. A nyitott kérdések lezárása mindig igazolt döntést vagy
ellenőrzést igényel; ezt a recovery checkpoint nem helyettesíti.
