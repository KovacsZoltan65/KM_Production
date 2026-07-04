# Learning Center v1.0 specifikáció

## Metaadatok

- Modul neve: Learning Center
- Verzió: v1.0
- Státusz: Draft
- Branch: feature/learning-center

## Cél

A Learning Center a KM_Production beépített tudás- és asszisztenciamodulja. Feladata az onboarding, az oldalspecifikus súgó, a kontextusfüggő tanulás, az operatív hibák magyarázata és a későbbi AI-alapú segítség előkészítése úgy, hogy közben ne jöjjön létre különálló dokumentációs sziget.

Ez a dokumentációs mappa a Learning Center v1.0 munkaspecifikációs struktúrája. A tartalom jelenleg tudatosan vázlatos, de minden dokumentumnak világos felelőssége van, hogy a későbbi tervezés, implementáció és tesztelés duplikált vagy ellentmondó tudás nélkül bővülhessen.

## Alapelvek

- A Learning Center egy közös tudásbázist használ egyetlen igazságforrásként.
- A hosszabb dokumentáció Markdown-alapú és verziókövetett.
- A dinamikus asszisztencia, tippek, hibák, metaadatok, felhasználói előrehaladás és oldalankénti asszisztenciaszintek adatbázisban tárolódnak.
- Minden oldalnak saját asszisztenciaszintje van.
- Az asszisztenciaszint minden támogatott oldalon látható.
- A felhasználó manuálisan módosíthatja az oldalankénti asszisztenciaszintet.
- Az automatikus visszalépés csak felhasználói jóváhagyással támogatott.
- Az asszisztencia nem kerülheti meg a Laravel jogosultságokat, validációt, üzleti szabályokat, traceability elvárásokat, készletmozgási szabályokat vagy auditkövetelményeket.
- A dokumentáció élő, kontextusfüggő, és az aktuális oldalhoz vagy workflow-hoz kapcsolódik.
- A modulnak készen kell állnia későbbi AI asszisztens integrációra.

## Fő komponensek

### Knowledge Engine

A Knowledge Engine felel a strukturált tudásért: Markdown oldalak, súgótémák, tippek, figyelmeztetések, példák, GYIK, hibamagyarázatok, screenshotok, videók és AI kulcsszavak. Arra ad választ, hogy mit tud a rendszer, és ez a tudás hogyan van rendszerezve.

### Learning Engine

A Learning Engine felel a tanulási viselkedésért: leckék, tanulási útvonalak, felhasználói előrehaladás, onboarding folyamatok, asszisztenciaszintek, ajánlások és adaptív javaslatok. Arra ad választ, hogy mit érdemes a felhasználónak legközelebb megtanulnia, és mennyi támogatásra van szüksége.

### Context Engine

A Context Engine felel a futásidejű kontextusért: aktuális oldal, felhasználói jogosultságok, meglévő adatok, hiányzó előfeltételek, validációs hibák, rendszerállapot és workflow státusz. Arra ad választ, hogy milyen segítség releváns éppen most.

## Gyors navigáció

- Kezdd a [vízióval](vision.md), ha a modul célját és hosszú távú szerepét keresed.
- Olvasd el a [Knowledge Unit](knowledge-unit.md) specifikációt, ha a Learning Center alapegységét kell megérteni.
- Folytasd a [Knowledge Graph](knowledge-graph.md) dokumentummal, ha a tudásegységek kapcsolatrendszere érdekel.
- Nézd meg a [Context Engine](context-engine.md) specifikációt, ha az oldal-, jogosultság- és állapotfüggő segítség döntési logikáját keresed.
- Használd a [decisions.md](decisions.md) dokumentumot az elfogadott architekturális döntések visszakeresésére.
- A projekt egészére vonatkozó nyelvi és lokalizációs szabályokat a [projektkonvenciók](../../architecture/project-conventions.md) és az [ADR-011](decisions.md#adr-011-projekt-nyelvi-es-lokalizacios-konvenciok) rögzíti.

## Dokumentumstruktúra

- [vision.md](vision.md): a Learning Center hosszú távú termékvíziója.
- [architecture.md](architecture.md): magas szintű modularchitektúra és határok.
- [knowledge-unit.md](knowledge-unit.md): a Learning Center alapegysége és fogalmi tudásmodellje.
- [knowledge-graph.md](knowledge-graph.md): tudásháló, kapcsolatok, ajánlások és AI navigáció.
- [knowledge-engine.md](knowledge-engine.md): tudásforrás, tartalomtípusok, publikálás és visszakeresés.
- [learning-engine.md](learning-engine.md): leckék, előrehaladás, ajánlások és adaptív tanulási viselkedés.
- [context-engine.md](context-engine.md): oldal-, felhasználó-, jogosultság- és állapotfüggő súgóválasztás.
- [data-model.md](data-model.md): tervezett entitások és kapcsolatok.
- [ui-ux.md](ui-ux.md): felületi területek és UX szabályok.
- [onboarding.md](onboarding.md): első használat és szerepköralapú onboarding.
- [assistance-levels.md](assistance-levels.md): Kezdő, Haladó és Profi viselkedés.
- [learning-paths.md](learning-paths.md): szerepkörspecifikus tanulási útvonalak.
- [live-documentation.md](live-documentation.md): kontextuális dokumentációs működés.
- [error-handling.md](error-handling.md): hibamagyarázati struktúra és példák.
- [ai-integration.md](ai-integration.md): későbbi AI integrációs határok.
- [permissions.md](permissions.md): láthatósági és szerkesztési jogosultságok.
- [roadmap.md](roadmap.md): v1.0-tól v2.0-ig tartó roadmap.
- [decisions.md](decisions.md): ADR jellegű döntésnapló.
- [future-ideas.md](future-ideas.md): hosszú távú ötlet-inkubátor a v1.0 scope-on túl.

## Implementációs fókusz

A v1.0 a strukturális alapokra fókuszáljon:

- Knowledge Unit fogalmi modell és tartalmi governance.
- Knowledge Graph kapcsolatok előkészítése.
- Tudásmodell és dokumentációs struktúra.
- Oldalszintű asszisztenciaszint tárolása és megjelenítése.
- Felhasználói profil az oldalankénti beállításokhoz.
- Kontextuális súgófelületek.
- Alap tanulási útvonalak és progress követés.
- Adminisztrálható metaadatok tippekhez, hibákhoz és témákhoz.
- AI-ready metaadatok teljes chatbot-követelmény nélkül.

## Nyitott kérdések

- Szerkeszthető legyen-e a Markdown tartalom admin UI-ból v1.0-ban, vagy csak Git-en keresztül?
- Mely oldalak kerüljenek be az első támogatott oldalregiszterbe?
- Mely tanulási útvonalak legyenek kötelezőek, és melyek ajánlottak?
- Mennyi ideig őrizzük meg a learning event adatokat?
- Tartalmazzon-e a v1.0 korlátozott AI help panelt, vagy csak az adatmodellt készítse elő?
- Milyen tartalmi review és publikálási workflow szükséges éles használat előtt?

## Kapcsolódó témák

- [Vízió](vision.md)
- [Architektúra](architecture.md)
- [Knowledge Unit](knowledge-unit.md)
- [Knowledge Graph](knowledge-graph.md)
- [Knowledge Engine](knowledge-engine.md)
- [Learning Engine](learning-engine.md)
- [Context Engine](context-engine.md)
- [Döntések](decisions.md)
- [Projektkonvenciók](../../architecture/project-conventions.md)
- [ADR-011: Projekt nyelvi és lokalizációs konvenciók](decisions.md#adr-011-projekt-nyelvi-es-lokalizacios-konvenciok)
- [Jövőbeni ötletek](future-ideas.md)
