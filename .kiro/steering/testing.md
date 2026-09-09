# Tesztelési szabályok

## Miért tesztelünk?

A tesztek azt igazolják, hogy az elvárt üzleti működés megmarad a változtatás
után is. Különösen a készlet, a gyártási nyomon követhetőség és a jogosultságok
hibái járhatnak súlyos következménnyel. A teszt a megfigyelhető eredményt
ellenőrizze azon a rétegen, ahol a szabályért felelős működés található.

Ez a dokumentum a teszttervezés tartós szabályait adja. A követelmények
alkalmazhatóságát és a lezárást a
[Definition of Done](../../docs/project-management/definition-of-done.md),
a futtatási szintet és az összeállított parancsokat a
[rétegezett ellenőrzések](../../docs/development/quality-gates.md) határozzák meg.
A tesztelési szabály, a futtató megvalósítása, a futtatási eljárás és egy
konkrét futás bizonyítéka külön fogalom.

## Szabály: a teszt terjedelme kövesse a kockázatot

Először a módosított viselkedést ellenőrizd a legszűkebb megfelelő teszttel.
Utána vizsgáld meg a közvetlenül kapcsolódó működés regressziós kockázatát,
majd a rétegezett útmutató szerint bővítsd az ellenőrzést.

- Kis, elkülönült változásnál elegendő lehet a célzott vizsgálat, ha lefedi a
  módosított működést és a kapcsolódó kockázatot.
- Modul működésének változásakor Module szintű, a modulra és kapcsolataira
  kiterjedő regressziós ellenőrzés szükséges.
- Több modult érintő közös alkalmazásműködésnél Integration szint szükséges.
- Projekt-, teszt-, build- vagy ellenőrzési infrastruktúra, függőségkezelés és
  globális keretrendszer-beállítás változása Full kockázatú. A migrációkat a
  jelenlegi besorolás szintén Full szintre helyezi.

A célzott siker nem váltja ki a szélesebb hatás miatt szükséges regressziót.
A szinteket nem kell egymás után mind lefuttatni. Az összesített tervből hiányzó,
de alkalmazandó vizsgálatot külön végezd el: a `composer qa:full` sem tartalmaz
MySQL-ellenőrzést vagy minden lehetséges böngészős vizsgálatot.

Csak dokumentációt érintő változáshoz a DoD szerinti formázás-, hivatkozás-,
parancspélda-, szóhasználat- és whitespace-ellenőrzés kell. Alkalmazásteszt vagy
új teszt hozzáadása nem automatikus követelmény minden változáshoz.

## Milyen viselkedést hol ellenőrizzünk?

### Unit tesztek

Kis hatókörű, elkülönült számításokat, enumokat, értékobjektumokat és
segédfüggvényeket vizsgálj velük. Legyenek gyorsak, a szélső esetekhez használj
konkrét példákat. Kerüld az adatbázist; ha tudatosan szükséges, a teszt
integrációs jellegét a besorolásnál és környezetválasztásnál is vedd figyelembe.

### Feature és backendintegrációs tesztek

A Laravel alkalmazás és a keretrendszer együttműködését vizsgálják: HTTP- és
Inertia-válaszokat, bemenetellenőrzést, jogosultságot és üzleti folyamatokat.
Ellenőrizd a sikeres és a fontos hibás eseteket. Az érintett működés szerint
vizsgáld az átirányítást, validációs hibát, adatváltozást és tevékenységnaplót.
Tiszta számítást ne csak teljes HTTP-folyamaton keresztül tesztelj.

A service teszt az üzleti műveletet ellenőrizze. Tranzakciós működésnél a
mentett állapot következetességét, szükség szerint a készletmozgást,
sorozatszámot, műveleti állapotot, naplót és kibocsátott eseményt vizsgáld.
Legyen eset érvénytelen állapotváltásra és hiányzó előfeltételre is.

A repository teszt az összetett lekérdezést, szűrést, rendezést, előre betöltött
kapcsolatokat és mentést ellenőrizze. A visszaadott modellhalmazt vagy lapozott
eredményt vizsgáld; ne másold le a Laravel saját tesztjeit.
A service és repository szerinti csoportosítás nem új kötelező tesztcsomag.

Jogosultságváltozásnál ellenőrizd a fontos policy-műveleteket és a Spatie
Permission szerepkörök, jogosultságok tényleges hatását. Jogosultság nélkül a
közvetlen hozzáférés is legyen tiltott. A szakmai munkakört ne keverd össze a
hozzáférési szerepkörrel a tesztadatokban sem.

### Frontendtesztek

A Vue-komponensek, oldalak, közös függvények és Inertia-hívási szerződések
viselkedését ellenőrzik. A projekt Vitest és Vue Test Utils eszközöket használ;
a tesztek helye `tests/frontend`. Részletek és futtatás:
[frontendtesztelés](../../docs/frontend-testing.md).

### E2E tesztek

A futó alkalmazás valódi felhasználói folyamatait vizsgálják Playwrighttal,
böngészőn keresztül. A tesztek helye `tests/e2e`. Az érintett folyamat és a
rétegezett szabályok alapján válassz; a komponens sikere önmagában nem bizonyítja
a teljes folyamatot. Az izolált környezetet és a böngészős eljárást az
[E2E-útmutató](../../docs/e2e-testing.md) írja le.

## Üzletileg kritikus területek és szélső esetek

Módosításkor vagy bevezetéskor igazold a sorozatszámképzést, készletmozgást,
anyagfelhasználást, műveleti sorrend verziózását, minőségellenőrzést,
részegységátadást és jogosultság-ellenőrzést, amennyiben érintettek.

A változás szerint válassz a fontos szélső esetekből:

- ismételt sorozatszám, párhuzamos vagy megismételt művelet;
- elégtelen készlet, érvénytelen műveleti sorrend vagy elavult sorrendverzió;
- elutasított vagy javítást igénylő minőségi eredmény;
- hiányzó jogosultság, érvénytelen dokumentumtípus, üres vagy részleges bemenet.

Ezek kockázati szempontok; nem minden teszthez kötelező esetsorok.

## Tesztminőség és regresszió

Hibajavításhoz készíts regressziós tesztet a javítás előtt vagy azzal együtt.
A név mondja el, milyen viselkedésnek kell megmaradnia. Pótold a változás által
indokolt, hiányzó kritikus lefedettséget; meglévő megfelelő tesztet ne másolj le.

Ne gyengíts elvárást, ne törölj értelmes tesztet és ne rejts el hibát kihagyással
csak azért, hogy sikeres legyen a futás. A hibás tesztet javítsd, ha az elvárt
üzleti szabály alapján valóban a teszt téves. Helyes üzleti működést ne írj át
egy téves teszt kedvéért. Sikertelen teszt kihagyása csak a DoD szerinti külön
szabályozási kezeléssel történhet; a kihagyás nem sikeres eredmény.

A teszt legyen determinisztikus és más tesztektől független. Dátumfüggő
esetben rögzített időt vagy kifejezett munkanapot használj. Az elvárás a kívülről
megfigyelhető viselkedést írja le, ne véletlen megvalósítási részletet.

Használj gyárakat (factory) az adatbázismodellekhez, érvényes alapadatokkal.
Csak a vizsgált esethez fontos értékeket írd felül; gyakori helyzethez használj
elnevezett factory-állapotot. Stabil dokumentumhoz vagy összetett bemenethez
használj előre rögzített tesztadatot (fixture). Ne legyen közös módosítható
állapot. A segédfüggvény csökkentse az ismétlést, de ne rejtse el a lényeges
előkészítést. A fájlnév és tesztnév kövesse az osztályt, funkciót vagy üzleti
folyamatot, és az elvárt viselkedést tegye érthetővé.

## Megvalósítás és futtatási eljárások

PHP-teszthez Pestet használj. A [phpunit.xml](../../phpunit.xml) a `tests/Unit`
és `tests/Feature` könyvtárakat választja ki, bootstrapje `vendor/autoload.php`.
A [tests/Pest.php](../../tests/Pest.php) a Feature tesztekhez rendeli a közös
[Tests\\TestCase](../../tests/TestCase.php) osztályt. Ez az alkalmazás indításakor
ellenőrzi a tesztkörnyezet biztonságát, és kikapcsolja a tesztek Vite-függését.
A teszteket funkció, service, repository vagy üzleti folyamat szerint csoportosítsd.

A [backendeljárás](../../docs/backend-quality-gate.md) tartalmazza a védett
SQLite/MySQL parancsokat, a célzott futást és a migrációvizsgálatot. SQLite
használható gyors és széles alkalmazásregresszióhoz. Ha a releváns kockázat a
termelési MySQL működésétől függ, MySQL-en is ellenőrizni kell. SQLite-siker
nem bizonyít MySQL-viselkedést, és az alkalmazásteszt nem helyettesíti a séma
oda-vissza migrációjának vizsgálatát. Csak dedikált, védett tesztadatbázist használj.

A [statikus elemzés](../../docs/static-analysis.md) külön eljárás: a típusok
ellenőrzése nem bizonyítja a futás közbeni üzleti helyességet. A pontos
végrehajtható parancsok forrása a [composer.json](../../composer.json) és a
[package.json](../../package.json); az összeállításuk és kiválasztásuk a
rétegezett útmutató feladata.

## Bizonyíték és a környezet elérhetősége

A futásról dátumot vagy futásazonosítót, parancsot, környezetet és megfigyelt
eredményt rögzíts. Korábbi siker vagy történeti audit nem az aktuális változás
bizonyítéka. A DoD négy eredményét a futás tényei szerint használd:

- `PASSED`: az adott ellenőrzés lefutott és sikeres.
- `FAILED`: a teszt lefutott és hibát talált.
- `BLOCKED`: igazolt külső vagy környezeti előfeltétel hiányzik, például nem
  érhető el az alkalmazandó MySQL-vizsgálat tesztszervere.
- `NOT RUN`: az ellenőrzést nem indították el, vagy egy korábbi hiba után
  kimaradt. Az előkészítés puszta elmaradása nem `BLOCKED`.

Böngészőindítási hiba, workerösszeomlás, időtúllépés vagy ingadozó teszteredmény
esetén előbb vizsgáld ki az okot. Ne minősítsd automatikusan sem alkalmazáshibának,
sem környezeti akadálynak. Őrizd meg a technikai hibakódot és a diagnózist.

Minden `FAILED`, `BLOCKED` és `NOT RUN` tételnél add meg az okot, hatást,
felelőst és következő lépést. Kötelező ellenőrzés hiánya mellett a feladat nem
teljesen kész. A valóban nem alkalmazandó vizsgálat rövid indokkal `N/A`;
ez nem egy kihagyott kötelező teszt eredménye.

Javítás után ismételd meg a hibás és a javítás által érintett vizsgálatokat,
és pótold a kimaradt kötelező lépéseket. A sikeres teljes tesztcsomagokat ne
futtasd újra indok nélkül; eredményük bizonyítékát őrizd meg.
