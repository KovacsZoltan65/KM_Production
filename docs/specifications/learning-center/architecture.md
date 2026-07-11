# Learning Center architektúra

## Cél

Ez a dokumentum a Learning Center modul tervezett magas szintű architektúráját és KM_Production rendszeren belüli határait rögzíti.

## Tervezett tartalom

- Illeszkedés a Laravel réteges architektúrához: Controller -> Service -> Repository -> Model.
- Knowledge Engine, Learning Engine és Context Engine szétválasztása.
- Markdown és adatbázis felelősségi körei.
- Integrációs pontok Inertia/Vue oldalakkal.
- Integrációs pontok jogosultságokkal, activity loggal és későbbi AI szolgáltatásokkal.
- v1.0 nem célok, különösen a közvetlen üzleti workflow módosítások kerülése.

## Architektúra vázlat

A modul hibrid dokumentációs architektúrát használjon. A Markdown fájlok tárolják a hosszabb, verziókövetett tudást. Az adatbázistáblák tárolják a dinamikus metaadatokat, kontextuális tippeket, hibamagyarázatokat, tanulási előrehaladást, felhasználó- és oldalfüggő asszisztenciaszinteket, valamint ajánlási állapotokat.

A Learning Center service-ek nem birtokolhatnak gyártási üzleti logikát. Magyarázhatnak, ajánlhatnak és vezethetnek, de a termelésért, készletért, beszerzésért, minőségért, dokumentumokért és jogosultságokért továbbra is a Laravel domain service-ek felelnek.

## Kapcsolódó témák

- [Knowledge Engine](knowledge-engine.md)
- [Learning Engine](learning-engine.md)
- [Context Engine](context-engine.md)
- [Adatmodell](data-model.md)
- [Jogosultságok](permissions.md)
