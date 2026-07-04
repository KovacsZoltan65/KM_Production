# Adatmodell

## Cél

Ez a dokumentum a Learning Center tervezett adategységeit és kapcsolatait rögzíti. A későbbi migrációk és implementációs tervezés vázlatos modellje.

## Tervezett tartalom

- Alap entitások.
- Kapcsolatok.
- Státuszmezők.
- Oldalszintű asszisztencia-beállítások.
- Progress követés.
- AI metaadatok.
- Migrációs tervezési szempontok.

## Tervezett entitások

- `KnowledgePage`: kanonikus, hosszabb tudáselem.
- `HelpTopic`: oldalhoz vagy workflow-hoz kötött rövid súgótéma.
- `Hint`: kontextuális inline útmutatás.
- `Warning`: megelőző útmutatás kockázatos műveletekhez.
- `ErrorExplanation`: validációs vagy workflow hibák strukturált magyarázata.
- `FAQ`: gyakori kérdés és válasz.
- `Example`: gyakorlati használati példa.
- `Lesson`: tanulási egység.
- `LessonStep`: interaktív lépés egy leckében.
- `LearningPath`: szerepköralapú tanulási útvonal.
- `LearningPathStep`: rendezett lecke-hivatkozás.
- `UserLearningProgress`: felhasználói előrehaladás leckéhez vagy útvonalhoz.
- `PageAssistanceLevel`: felhasználó- és oldalspecifikus asszisztenciaszint.
- `LearningEvent`: analitikai esemény.
- `LearningRecommendation`: generált ajánlás.
- `LearningAsset`: screenshot, videó, PDF vagy kép metaadata.
- `AiKnowledgeKeyword`: AI és keresési visszakereséshez használt kulcsszó metaadat.

## Kapcsolódó témák

- [Knowledge Engine](knowledge-engine.md)
- [Learning Engine](learning-engine.md)
- [Jogosultságok](permissions.md)
- [AI integráció](ai-integration.md)
