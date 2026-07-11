# Knowledge Engine

## Cél

A Knowledge Engine meghatározza, hogyan készül, strukturálódik, publikálódik, kereshető vissza és használható újra a Learning Center tudása dokumentációban, oldalsúgóban, GYIK-ban, hibamagyarázatokban és későbbi AI válaszokban.

## Tervezett tartalom

- Tudásoldal életciklus.
- Markdown forrásstratégia.
- Adatbázis-alapú témák, tippek, figyelmeztetések, hibák, példák, GYIK és metaadatok.
- Single Source of Truth szabályok.
- Publikálási és review státusz.
- Keresési és visszakeresési stratégia.
- AI kulcsszó metaadatok.

## Vázlatos specifikáció

A Knowledge Engine célja a duplikált tartalom elkerülése. A hosszú magyarázatok Markdown tudásoldalakban éljenek. A rövid kontextuális rekordok ezekre az oldalakra hivatkozzanak, ne másoljanak át nagy szövegrészeket. A súgótémák, tippek és hibamagyarázatok lehetőség szerint kanonikus tudásoldalakra mutassanak vissza.

A tudásrekordok támogassák a modul, page key, célközönség, locale, státusz és szükséges jogosultság metaadatokat. Végfelhasználónak csak publikált és jogosultság szerint elérhető tartalom jelenhet meg.

## Kapcsolódó témák

- [Adatmodell](data-model.md)
- [Élő dokumentáció](live-documentation.md)
- [Hibakezelés](error-handling.md)
- [AI integráció](ai-integration.md)
