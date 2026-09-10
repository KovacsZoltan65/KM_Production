# KM_Production backlog-konvenciók

## Cél és hatókör

Egy backlogelem egy megnevezett, ellenőrizhető eredményhez tartozó munkát ír le:
miért szükséges, mi tartozik bele, mitől függ, és mi van még hátra. A
[központi backlog](backlog.md) a jóváhagyott munka nyilvántartása, a
[következő lépések](next-actions.md) a közeli teendőket emelik ki belőle.
A specifikáció, ötletlista, Git-ág vagy commit önmagában nem igazolja, hogy
egy feladat aktív vagy kész.

Ez a dokumentum a bejegyzések szerkezetét és munkaállapotát szabályozza.
A lezárás elsődleges szabálya a [Definition of Done](definition-of-done.md),
az ellenőrzések kiválasztásáé a [rétegezett útmutató](../development/quality-gates.md).
A felsorolás nem ad végrehajtási felhatalmazást; az AI-agentre az
[AGENTS.md](../../AGENTS.md) szerinti engedélyezési szabályok érvényesek.

## ID-formátum

Az ID stabil, újra nem használható, és a kategória előtagjából, valamint
háromjegyű sorszámból áll:

| Előtag  | Kategória                                                                 |
| ------- | ------------------------------------------------------------------------- |
| `GOV`   | Projektvezetés és Git                                                     |
| `CI`    | CI és release                                                             |
| `TEST`  | Tesztelés és statikus elemzés                                             |
| `AUD`   | Meglévő auditnaplózási tétel a Tesztelés és statikus elemzés kategóriában |
| `CACHE` | Meglévő cache-tétel a Tesztelés és statikus elemzés kategóriában          |
| `LC`    | Learning Center                                                           |
| `OCR`   | Document Intelligence és OCR                                              |
| `MI`    | Manufacturing Intelligence                                                |
| `OPS`   | Üzemeltetés                                                               |
| `UX`    | UX és skálázhatóság                                                       |

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

A prioritás az ütemezési és üzleti fontosságot jelöli. Nem ellenőrzési
eredmény, hibasúlyosság, készültség vagy merge-engedély.

## Állapotok

| Állapot       | Belépési feltétel                                                                    |
| ------------- | ------------------------------------------------------------------------------------ |
| `planned`     | Jóváhagyott irány, de hiányzik döntés, bontás vagy előfeltétel.                      |
| `ready`       | A scope, függőségek és elfogadási feltételek végrehajtásra készek.                   |
| `in-progress` | Van tényleges munkavégzés és kijelölt végrehajtó ág vagy issue.                      |
| `blocked`     | Konkrét, dokumentált akadály miatt a munka érdemben nem folytatható.                 |
| `review`      | A megvalósítás elkészült; ellenőrzés vagy felülvizsgálat még nyitott.                |
| `done`        | Minden alkalmazandó elfogadási feltétel és DoD-követelmény rendezett, bizonyítékkal. |
| `cancelled`   | A feladatot indokolt döntéssel elvetették vagy kiváltották.                          |

Specifikáció vagy feature ág létezése nem jelent `in-progress` állapotot.
Minden `blocked` tételnél meg kell nevezni a blokkoló okot és a feloldás
feltételét. Az állapot legalább minden release-tervezéskor felülvizsgálandó.

A `ready` végrehajtásra előkészített feladatot jelent, nem merge-ready állapotot.
Az „implementált” leírás, nem új állapot: a kód vagy dokumentum létezése nem
bizonyít teljes ellenőrzést, review-t, kiadhatóságot vagy telepítést.
A `partially done` nem hivatalos állapot. Részleges munkánál a tényleges
helyzethez illő meglévő állapotot használd, és külön írd le a hátralévő részt.
Ellentmondó forrásnál nevezd meg a bizonytalanságot és a tisztázó lépést;
állapotot ne változtass puszta feltételezésből.

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

Az alkalmazandó feltételek együtt igazolják:

1. a kívánt eredményt;
2. a fontos hibás vagy jogosulatlan eset kezelését, ha a változás érinti;
3. a kockázat szerint kiválasztott ellenőrzések eredményét.

Dokumentációs munkára ne írj elő automatikusan alkalmazástesztet. Történeti
tesztszám vagy lefedettségi százalék nem válik állandó elfogadási feltétellé.

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

A függőség létező backlog ID-ra hivatkozik; körkörös függőség nem engedélyezett.
A `Nincs` nem igazolja a külső környezet elérhetőségét vagy a végrehajtási
engedélyt. Az előfeltételeket a munka megkezdése előtt ellenőrizd.

Külső akadálynál külön jegyezd fel, mi nem folytatható, mely előfeltétel
hiányzik, mi a hatása, ki vagy mely szerep oldja fel, és mi a következő lépés.
Jelezd, ha a megvalósítás egyébként elkészült. Pusztán hátralévő munka nem
külső akadály. Ha más érdemi munka folytatható, egy akadályozott ellenőrzés
mellett a feladat lehet `in-progress` vagy `review` is.

## Lezárási és Definition of Done szabály

A feladatspecifikus elfogadási feltétel azt mondja meg, mit kell szállítani; a
[projektszintű Definition of Done](definition-of-done.md) azt, hogy milyen
minőségben és bizonyítékkal tekinthető késznek. Egy aktív tétel csak minden AC,
valamint a releváns általános és változástípus-specifikus DoD-pont
teljesülésekor állítható `done` állapotba.

A DoD szerinti `PASSED`, `FAILED`, `BLOCKED`, `NOT RUN` ellenőrzési eredmények,
nem backlogállapotok. A munkát megállító `blocked` és egy környezeti okból
akadályozott vizsgálat `BLOCKED` eredménye nem automatikusan ugyanaz.
A lefutott, tiltott sérülékenységet találó audit `FAILED`, nem `BLOCKED`;
az azt rendező feladat ettől még lehet `in-progress`.

A backlogban röviden szerepeljen az előrehaladás, a nyitott ellenőrzés, annak
eredménye és a következő lépés; a részletes bizonyítékra hivatkozz. A nem sikeres
ellenőrzések okát, hatását, felelősét és rendezését a DoD szerint kell megadni.
Kötelező hiányt egy állapotcímke vagy leírt kivétel nem tesz teljesítetté.
A kockázatelfogadás feltételeit kizárólag a DoD határozza meg.

## Dokumentációfrissítés

- Új vagy módosított backlogelem esetén frissíteni kell a központi backlog
  összesítő tábláit.
- A kiemelt következő lépések változásakor frissíteni kell a
  [next-actions.md](next-actions.md) fájlt. A lista nem második backlog és nem
  jelenti, hogy minden felsorolt tétel előfeltétele teljesült.
- Új mérést dátummal, környezettel és bizonyítékhivatkozással rögzíts; a régi
  auditot ne írd át aktuális eredménnyé.
- A backlogban kizárólag relatív projektútvonal használható.
- A roadmap, specifikáció és backlog közötti ellentmondást nem szabad csendben
  feloldani; külön döntési vagy scope-záró tételt kell létrehozni.

A dátumot `YYYY-MM-DD` alakban és jelentéssel add meg: például kiinduló
állapot, célidőpont, eredmény, lezárás vagy dokumentum-felülvizsgálat.
A célverzió nem határidő. Egy régi céldátum, siker vagy akadály nem a mai
helyzet bizonyítéka. A lezárt tételeket őrizd meg az akkori eredményükkel;
a későbbi változás igazolása külön szükséges. Az összesítő a rögzített
állapotokat számolja, nem helyettesíti a lezárási bizonyíték ellenőrzését.

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
