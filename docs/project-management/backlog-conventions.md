# KM_Production backlog-konvenciók

## Cél és hatókör

Ez a dokumentum a `docs/project-management/backlog.md` kötelező szerkezeti és
karbantartási szabályait rögzíti. A központi backlog a végrehajtható,
jóváhagyott projektmunka elsődleges nyilvántartása. A specifikáció, ötletlista,
Git-ág vagy commit önmagában nem igazolja, hogy egy feladat aktív vagy kész.

## ID-formátum

Az ID stabil, újra nem használható, és a kategória előtagjából, valamint
háromjegyű sorszámból áll:

| Előtag | Kategória                     |
| ------ | ----------------------------- |
| `GOV`  | Projektvezetés és Git         |
| `CI`   | CI és release                 |
| `TEST` | Tesztelés és statikus elemzés |
| `LC`   | Learning Center               |
| `OCR`  | Document Intelligence és OCR  |
| `MI`   | Manufacturing Intelligence    |
| `OPS`  | Üzemeltetés                   |
| `UX`   | UX és skálázhatóság           |

Az ID cím- vagy kategóriaváltás után sem módosul. Törlés helyett a tétel
`cancelled` állapotba kerül, indoklással.

## Kötelező mezők

Minden backlogelem tartalmazza az alábbi mezőket:

- ID és cím;
- állapot;
- prioritás;
- kategória;
- célverzió;
- összefoglaló;
- indoklás;
- scope;
- scope-on kívül;
- függőségek;
- elfogadási feltételek;
- tesztelési követelmények;
- kapcsolódó fájlok és dokumentáció;
- becsült méret;
- kockázat.

Ismeretlen adat helyett döntést előkészítő feladatot kell létrehozni. A mező
nem hagyható el és nem tölthető ki homályos „később” értékkel.

## Prioritások

| Prioritás | Jelentés                                                                     |
| --------- | ---------------------------------------------------------------------------- |
| `P0`      | Kritikus: adat-, biztonsági, release- vagy projektkövetési kockázatot kezel. |
| `P1`      | Magas: stabilitási kapu vagy a következő fejlesztési fázis előfeltétele.     |
| `P2`      | Közepes: tervezett üzleti érték, amely nem blokkolja a jelenlegi működést.   |
| `P3`      | Alacsony/későbbi: kutatási vagy hosszabb távú termékirány.                   |

Prioritás módosításakor a backlog változásnaplójában vagy a kapcsolódó pull
requestben rögzíteni kell az okot.

## Állapotok

| Állapot       | Belépési feltétel                                                          |
| ------------- | -------------------------------------------------------------------------- |
| `planned`     | Jóváhagyott irány, de hiányzik döntés, bontás vagy előfeltétel.            |
| `ready`       | A scope, függőségek és elfogadási feltételek végrehajtásra készek.         |
| `in-progress` | Van tényleges munkavégzés és kijelölt végrehajtó ág vagy issue.            |
| `blocked`     | Konkrét, dokumentált blokkoló akadály áll fenn.                            |
| `review`      | Az implementáció elkészült, de még nem teljesítette az összes ellenőrzést. |
| `done`        | Az elfogadási és tesztelési feltételek bizonyítottan teljesültek.          |
| `cancelled`   | A feladatot indokolt döntéssel elvetették vagy kiváltották.                |

Specifikáció vagy feature ág létezése nem jelent `in-progress` állapotot.
Minden `blocked` tételnél meg kell nevezni a blokkoló okot és a feloldás
feltételét. Az állapot legalább minden release-tervezéskor felülvizsgálandó.

## Célverziók

Engedélyezett kezdő célverziók:

- `v1.x Stabilizálás`;
- `Learning Center v1.0`;
- `Document Intelligence v1.0`;
- `Learning Center v1.1`;
- `Learning Center v1.2`;
- `Manufacturing Intelligence v2`;
- `Future / Unscheduled`.

Feladat csak akkor kerül verzióhoz, ha annak dokumentált kapcsolata van a
mérföldkővel. A nem jóváhagyott ötlet `Future / Unscheduled` vagy a „Nem
vállalt ötletek” részbe kerül.

## Becsült méret

| Méret | Irányadó terjedelem                          |
| ----- | -------------------------------------------- |
| `XS`  | Néhány óra, izolált változtatás.             |
| `S`   | Legfeljebb 1–2 munkanap.                     |
| `M`   | Több komponens, néhány munkanap.             |
| `L`   | Több alkalmazásréteg vagy rendszerhatár.     |
| `XL`  | Külön mérföldkő; végrehajtás előtt bontandó. |

`XL` elem csak discovery vagy mérföldkő-szintű nyilvántartás lehet. Közvetlen
végrehajtás előtt kisebb, önállóan ellenőrizhető tételekre kell bontani.

## Elfogadási feltételek

Az elfogadási feltétel megfigyelhető eredményt ír le, lehetőleg paranccsal,
rekordszámmal, engedélyezési esettel vagy felhasználói folyamattal. Nem
használható például a „stabilabb”, „jobb UX” vagy „AI fejlesztése” megfogalmazás
mérőszám nélkül.

Legalább egy feltételnek igazolnia kell:

1. a kívánt eredményt;
2. egy fontos hibás vagy jogosulatlan eset kezelését;
3. a releváns quality gate eredményét.

## Feladatbontási szabályok

- Egy tétel egy elsődleges eredményt szállítson.
- Külön tétel kell eltérő réteghez, ha az külön is ellenőrizhető vagy más
  feladattól függ.
- Meglévő infrastruktúra ellenőrzése nem nevezhető új infrastruktúra
  létrehozásának.
- A már implementált MES-funkció csak regresszió, javítás vagy dokumentált
  bővítés esetén kerülhet vissza aktív backlogba.
- Ötletlistából csak jóváhagyott roadmap-, verzió- vagy döntéshivatkozással
  lehet aktív tételt létrehozni.
- Üzleti logikát érintő feladatnak meg kell neveznie a vonatkozó ADR-t,
  domain-dokumentumot és traceability-követelményt.

## Függőségek

A függőség kizárólag létező backlog ID lehet. A `Nincs` érték azt jelenti, hogy
a tétel önállóan kezdhető. Körkörös függőség nem engedélyezett. Külső blokkoló
nem függőségként, hanem a `blocked` állapot indoklásában szerepel.

## Lezárási és Definition of Done szabály

A feladatspecifikus elfogadási feltétel azt mondja meg, mit kell szállítani; a
[projektszintű Definition of Done](definition-of-done.md) azt, hogy milyen
minőségben és bizonyítékkal tekinthető késznek. Egy aktív tétel csak minden AC,
valamint a releváns általános és változástípus-specifikus DoD-pont
teljesülésekor állítható `done` állapotba.

Ha egy ellenőrzés környezeti okból nem futtatható, a tétel legfeljebb `review`
állapotú lehet, dokumentált eltéréssel. A részleges munka `in-progress`,
`review` vagy `blocked`; a hiányzó feltételt és a következő lépést rögzíteni
kell.

## Dokumentációfrissítés

- Új vagy módosított backlogelem esetén frissíteni kell a központi backlog
  összesítő tábláit.
- A következő tíz végrehajtható feladat változásakor frissíteni kell a
  `docs/project-management/next-actions.md` fájlt.
- Mért projektállapot csak dátumozott auditban módosítható.
- A backlogban kizárólag relatív projektútvonal használható.
- A roadmap, specifikáció és backlog közötti ellentmondást nem szabad csendben
  feloldani; külön döntési vagy scope-záró tételt kell létrehozni.

## GitHub issue-ra történő leképezés

Későbbi GitHub issue létrehozásakor:

- az issue címének eleje a backlog ID;
- egy backlogelem egy elsődleges issue-hoz tartozik;
- a backlog marad a prioritás, célverzió és függőség kanonikus forrása;
- az issue tartalmazza a backlog relatív hivatkozását;
- a label a kategóriát és prioritást tükrözi;
- a milestone a célverzióval egyezik, ha létezik;
- az issue lezárása önmagában nem állítja a backlogelemet `done` állapotba;
- issue felosztásakor az eredeti backlogelem függőségeit és scope-ját
  felül kell vizsgálni.

Automatikus issue-szinkron csak külön jóváhagyott folyamat és jogosultsági
modell után vezethető be.
