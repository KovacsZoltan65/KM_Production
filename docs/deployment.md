# Deployment

## Purpose

This page records deployment-facing documentation for KM_Production.

## Current Guidance

Deployment must preserve application reliability, traceability, database integrity, permissions, and auditability.

Before deployment, verify the relevant quality gates:

- [.kiro/checklists/before-merge.md](../.kiro/checklists/before-merge.md)
- [.kiro/checklists/before-commit.md](../.kiro/checklists/before-commit.md)
- [.kiro/checklists/release.md](../.kiro/checklists/release.md)
- [.kiro/checklists/security.md](../.kiro/checklists/security.md)

## Deployment Notes

- Keep secrets in environment configuration, not documentation or source control.
- Run database changes through Laravel migrations.
- Do not manually alter production inventory quantities.
- Preserve audit logs and document storage.
- Confirm frontend assets are built before release.

## Biztonságos projekt-export

A teljes projektmappa kézi ZIP-be tömörítése nem biztonságos, mert a csomagba helyi környezeti fájlok, naplók, függőségek, cache-ek, adatbázisok vagy fejlesztői segédfájlok is bekerülhetnek.

A commitolt projektállapot biztonságos exportja a repository gyökeréből:

```powershell
.\scripts\export-project.ps1
```

Egyedi kimeneti fájl vagy könyvtár is megadható:

```powershell
.\scripts\export-project.ps1 -OutputPath C:\Exports\KM_Production-support.zip
```

Ha a célfájl már létezik, a script egyedi, sorszámozott fájlnevet választ, és nem írja felül. Az export `git archive` használatával kizárólag az aktuális commitban szereplő fájlokból készül, a `.gitattributes` `export-ignore` szabályainak alkalmazásával. A `.env.example` része marad a csomagnak, de a valódi `.env*` fájlok, hitelesítő adatok, naplók, IDE-fájlok, függőségek, coverage, build artifactok, cache-ek, helyi adatbázisok és mentések kimaradnak. A script elkészítés után automatikusan ellenőrzi a ZIP tartalmát, és hibás csomagot nem hagy meg.

A még nem commitolt módosítások szándékos átadásához előbb ellenőrizd őket, készíts külön commitot, majd futtasd újra az exportot. A projektkönyvtár közvetlen másolása vagy tömörítése erre sem biztonságos alternatíva.

Ha egy titok korábban csomagba, Git-előzménybe vagy illetéktelen személyhez került, a fájl későbbi törlése nem elég: az érintett kulcsot, jelszót vagy tokent vissza kell vonni és rotálni kell.

## Related Documentation

- [Architecture](architecture.md)
- [Manufacturing](manufacturing.md)
- [Security steering](../.kiro/steering/security.md)
- [Release workflow](../.kiro/workflows/release.md)
