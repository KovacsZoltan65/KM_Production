# Telepítés

A telepítés célja, hogy a kiválasztott kiadás a célkörnyezetben ellenőrzötten
működjön, az adatbiztonság, jogosultságok és gyártási nyomon követhetőség
megőrzésével. Ez az útmutató az előkészítést, végrehajtást és utóellenőrzést
választja szét; nem tartalmaz teljes, környezetspecifikus éles parancssort.

A készültség és az ellenőrzési eredmények mérvadó forrása a
[Definition of Done](project-management/definition-of-done.md) (DoD), az
ellenőrzések kiválasztásáé a [rétegezett útmutató](development/quality-gates.md).
A kiadás menetét a [kiadási folyamat](../.kiro/workflows/release.md), teendőit a
[kiadási lista](../.kiro/checklists/release.md) foglalja össze.

## Előfeltételek

A merge-ready állapot a DoD és a felülvizsgálat alkalmazandó követelményeinek
rendezését jelenti. A release-ready állapothoz a kiadás saját követelményei is
kellenek. A deployment-ready állapot ezen felül a célkörnyezet működési
előfeltételeinek teljesülését is megköveteli. Egyik állapot sem bizonyítja előre
a telepítés sikerét.

Telepítés előtt legyen azonosítható a kiadás verziója, tartalma, célkörnyezete és
az azt lefedő ellenőrzési bizonyíték. Az alkalmazandó kötelező vizsgálatok legyenek
rendezettek. A kiadás ténye nem ír elő automatikusan minden projektellenőrzést;
a csak dokumentációt érintő változásra is a DoD alkalmazhatósági szabályai érvényesek.

A célkörnyezet tervében rögzítsd:

- a végrehajtót, szükséges hozzáféréseket és felhatalmazást;
- a csomag, függőségek és szükséges frontend build előállítását és ellenőrzését;
- a konfigurációt, migrációkat, jogosultságokat, szerepköröket és környezetbiztos seedereket;
- a queue workerek, ütemezett feladatok, dokumentumtárolás és érintett AI/OCR feldolgozás teendőit;
- a mentést, helyreállítási lehetőséget, kiesési kockázatot és lépéssorrendet;
- a kötelező utóellenőrzéseket, visszaállítási feltételeket és eszkalációért felelőst.

A titkok a környezeti konfigurációba tartoznak, nem forrásba, csomagba vagy
jelentésbe. Adatbázis-változást Laravel migrációval végezz; készletmennyiséget
csak készletmozgással változtass. Őrizd meg az auditnaplókat, dokumentumokat és
nyomon követhetőségi adatokat.

Adatbázis-változásnál tedd explicitté a MySQL-ellenőrzés alkalmazhatóságát.
Ha szükséges, SQLite vagy helyi `qa:full` nem helyettesíti. Elérhetetlen szükséges
MySQL tesztkörnyezet esetén az eredmény `BLOCKED`, a kiadási készültség nem igazolt.

**Dokumentációs hiány:** ez az útmutató nem határozza meg az éles célkörnyezet
konkrét telepítési, mentési/helyreállítási és workerkezelési parancsait vagy
eszkalációs kapcsolattartóját. Ezeket a tényleges környezethez igazolva kell
rögzíteni a telepítés előtt. Fejlesztői vagy tesztkörnyezet-előkészítő szkriptből
nem következik éles telepítési eljárás.

Az [AGENTS.md](../AGENTS.md) szerinti felhatalmazás szükséges: AI-ügynök nem
commitolhat, pusholhat, hozhat létre vagy módosíthat PR-t, végezhet merge-öt,
hozhat létre kiadást, telepíthet vagy állíthat vissza éles rendszert kifejezett
felhasználói felhatalmazás nélkül. Ez az útmutató nem ad ilyen engedélyt.

## Telepítési eljárás

1. Azonosítsd a kiválasztott kiadást, és igazold a fenti előfeltételeket.
2. Készítsd elő és ellenőrizd a terv szerinti csomagot és frontend asseteket.
   A [package.json](../package.json) `npm run build` parancsa Vite buildet készít;
   önmagában nem telepít és nem igazolja a célkörnyezet működését.
3. Ellenőrizd a terv szerinti mentést és a helyreállítás lehetőségét.
4. A célkörnyezethez rögzített sorrendben hajtsd végre a csomag, konfiguráció,
   migrációk, jogosultságok és érintett háttérfolyamatok szükséges lépéseit.
   Hibánál állj meg, és alkalmazd a hibakezelést.
5. Rögzítsd a végrehajtott lépéseket, időpontokat és eredményeket, majd végezd el
   az utóellenőrzést. A sikeres parancskilépés önmagában nem sikeres telepítés.

## Telepítés utáni ellenőrzés

A kiadás hatása alapján ellenőrizd és bizonyítékkal rögzítsd:

- az alkalmazás indulását és a szükséges állapotellenőrzéseket;
- a migrációk várt állapotát és az érintett adatbázis-működést;
- a célfunkciót és jogosultságait, valamint a közvetlen kritikus regressziók hiányát;
- az érintett frontend assetek, háttérfeladatok és tárolás működését;
- a beállított alkalmazásnaplókban az új kritikus hibák hiányát;
- a terv szerinti visszaállítás rendelkezésre állását az ellenőrzés lezárásáig.

A [bootstrap/app.php](../bootstrap/app.php) a `/up` állapotvégpontot állítja be.
A [böngészős tesztelési útmutató](e2e-testing.md) indulási ellenőrzései között
`GET /up` és `GET /login` 200 válasz, valamint manifest- és assetellenőrzés szerepel.
Ezek kiindulópontot adnak az elérhetőség vizsgálatához, de nem bizonyítják a teljes
adatbázis- vagy üzleti működést. A tesztkörnyezet előkészítése nem éles eljárás.

A naplózást a [config/logging.php](../config/logging.php) és a célkörnyezet
`LOG_CHANNEL`, illetve `LOG_STACK` beállítása határozza meg; a tényleges csatornát
ellenőrizd. Ez a dokumentum nem határoz meg automatizált monitorozási szerződést
vagy riasztási küszöböket. Az alkalmazandó működési ellenőrzések módját ezért a
célkörnyezet tervében kell konkretizálni.

Csak a befejezett telepítés és a sikeres kötelező utóellenőrzések együtt igazolnak
sikeres telepítést. A végrehajtást és az ellenőrzést külön eredménnyel jelentsd.

## Hibakezelés

Sikertelen végrehajtás vagy utóellenőrzés esetén állítsd meg a továbblépést,
őrizd meg a hiba bizonyítékát, és mérd fel az üzemi, adatbiztonsági és
jogosultsági hatást. Értesítsd a tervben megjelölt felelőst a szükséges
felhatalmazás szerint; kövesd a visszaállítási vagy eszkalációs tervet.

A DoD eredményeit alkalmazd: `PASSED` a sikeresen lefutott, `FAILED` a
sikertelenül lefutott ellenőrzés; `BLOCKED` az azonosított külső/környezeti
előfeltétel miatt érdemben nem végezhető vizsgálat, `NOT RUN` a le nem futott.
A tiltott sérülékenységet találó audit `FAILED`, nem `BLOCKED`. Ha az auditot
valóban elérhetetlen külső szolgáltatás akadályozza, `BLOCKED` lehet az eredmény.

A kötelező ellenőrzés `FAILED`, `BLOCKED` vagy `NOT RUN` eredménye megakadályozza
a teljes készültség igazolását. Eltéréshez a DoD szerinti külön, kifejezett,
felhatalmazott kockázatelfogadó döntés kell; az indoklás és a döntés sem változtatja
az eredményt `PASSED`-re. Az utóellenőrzés hibája `FAILED` marad akkor is, ha a
telepítés technikailag befejeződött; ezt ne nevezd sikeres telepítésnek.

A jelentésben szerepeljen a kiadás és környezet, időpont, végrehajtó,
felhatalmazás, bizonyíték, fennmaradó kockázat, felelős és következő lépés.
Dátumozott korábbi auditból ne következtess a jelen környezet állapotára.

## Visszaállítás

A tervben előre rögzítsd, milyen hibánál szükséges visszaállítás, és milyen
adat- vagy migrációs korlát mellett nem biztonságos. Ha a feltétel teljesül,
a szükséges felhatalmazással hajtsd végre az előkészített eljárást.
Az alkalmazáskód visszaállítása nem bizonyítja az adatbázis helyreállítását;
vissza nem fordítható változásnál külön helyreállítási döntés kell.

Ha a visszaállítás nem hajtható végre biztonságosan, eszkaláld a helyzetet;
ne rögtönözz adatvesztéssel járó lépéseket. A visszaállítás után is ellenőrizd az
alkalmazást és az érintett adatokat. Rögzítsd a kiváltó okot, döntést,
felhatalmazást, visszaállított verziót, adatállapotot, lépéseket, eredményeket és
fennmaradó kockázatot. A helyreállítás sikere nem írja át az eredeti telepítés hibáját.

## Projektforrás exportálása

A teljes projektmappa kézi ZIP-be tömörítése helyi titkokat, naplókat,
függőségeket, cache-eket és adatbázisokat is átadhat. A
[scripts/export-project.ps1](../scripts/export-project.ps1) a commitolt forrás
szabályozott átadására szolgál; az export nem kész telepítési csomag, többek
között a függőségek és a build kimenete sem része.

**Ismert eltérés:** a [.gitattributes](../.gitattributes) exportálhatónak jelöli a
verziókövetett `.env.e2e.example` és `.env.testing.example` fájlokat, az exportáló
ZIP-ellenőrzése viszont a `.env*` fájlok közül csak a `.env.example` fájlt engedi.
A jelen szabályok alapján ez ellenőrzési hibához és az új archívum törléséhez vezet.
Az alábbi parancsok léteznek, de sikeres exportjuk nem feltételezhető; az eltérés
külön javítást igényel. Az ellenőrzést ne kerüld meg.

A repository gyökeréből használható parancsok:

```powershell
.\scripts\export-project.ps1
.\scripts\export-project.ps1 -OutputPath C:\Exports\KM_Production-support.zip
```

A futtatáshoz PowerShell és Git szükséges. Meglévő célfájlnál a szkript egyedi,
sorszámozott nevet választ, nem ír felül. A `git archive` a `HEAD` fájltartalmát
exportálja; a `--worktree-attributes` miatt az exportálási szabályok a munkafa
`.gitattributes` fájljából származnak, ezért azt is ellenőrizni kell.

A szabályok és a ZIP-ellenőrzés a valódi környezeti fájlok, ismert hitelesítő
fájlnevek, naplók, IDE-segédfájlok, függőségek, coverage, build kimenetek,
cache-ek, helyi adatbázisok és mentések kizárását szolgálják. Ez fájlnév- és
útvonalellenőrzés, nem általános titoktartalom-vizsgálat. Az elkészített csomag
ellenőrzési hibájánál a szkript törli az új archívumot.

Nem commitolt módosítás átadásához előbb vizsgáld felül a változásokat; külön
commitot csak a szükséges felhatalmazással készíts, majd az exporthibák rendezése
után futtasd újra az exportot. A közvetlen mappatömörítés nem biztonságos kerülőút.
Ha titok csomagba, Git-előzménybe vagy illetéktelenhez került, törlése nem elég:
az érintett kulcsot, jelszót vagy tokent vissza kell vonni és cserélni kell.

## Kapcsolódó dokumentáció

- [Architektúra](architecture.md)
- [Gyártás](manufacturing.md)
- [Biztonsági irányelvek](../.kiro/steering/security.md)
- [Biztonsági ellenőrzőlista](../.kiro/checklists/security.md)
- [Sürgős hibajavítás](../.kiro/workflows/hotfix.md)
