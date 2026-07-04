# AI integráció

## Cél

Ez a dokumentum azt definiálja, hogyan kapcsolódhat később a Learning Center AI asszisztenciához úgy, hogy közben megőrzi a KM_Production üzleti szabályait és biztonsági határait.

## Tervezett tartalom

- AI-ready tudásmetaadatok.
- Kontextus payload szabályok.
- Retrieval szűrés.
- Felhasználói jogosultságok az AI kontextusban.
- Emberi nyelvű magyarázatok.
- v1.0 AI nem célok.
- Későbbi chatbot és oktató képességek.

## Vázlatos specifikáció

Az AI használhatja a Learning Center tudásbázisát oldalfelismerésre, kontextuális segítségre, hibakeresésre, ajánlásokra és vezetett oktatásra. Az AI kimenete tanácsadó jellegű marad. Az autorizációért, validációért, perzisztenciáért, workflow átmenetekért, audit logért, készletszabályokért, traceability-ért és végső üzleti döntésekért továbbra is a Laravel felel.

Az AI kontextust oldal, jogosultság, locale, státusz és publikált tudástartalom alapján szűrni kell. Az AI nem kaphatja meg minden kérésnél a teljes tudásbázist.

## Kapcsolódó témák

- [Knowledge Engine](knowledge-engine.md)
- [Context Engine](context-engine.md)
- [Jogosultságok](permissions.md)
- [Döntések](decisions.md)
