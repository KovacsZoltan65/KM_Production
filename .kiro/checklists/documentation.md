# Dokumentációs ellenőrzőlista

## Cél és alkalmazhatóság

A lista az érintett dokumentáció minőségét és teljességét vizsgálja.
A készültség és az eredmények szabályát a
[Definition of Done](../../docs/project-management/definition-of-done.md) (DoD),
az arányos ellenőrzés kiválasztását a
[rétegezett útmutató](../../docs/development/quality-gates.md) adja.
A lista nem második DoD és nem módosítási, staging- vagy commitengedély;
a hatókört az [AGENTS.md](../../AGENTS.md) és a jelenlegi felhasználói kérés adja.

- [ ] Megvizsgáltam, változik-e valami, amit a fejlesztőnek, agentnek vagy
      felhasználónak értenie kell: üzleti döntés, domainismeret, API, konfiguráció,
      fejlesztési eljárás, felhasználói vagy üzemeltetési működés, jogosultság.
- [ ] Az érintett leírás frissült a felhatalmazott körben. Ha a dokumentált
      tartalom nem változik, ezt rövid `N/A` indok jelzi. Szükséges, de hiányzó
      dokumentációt nem minősítettem nem alkalmazandónak; hiányát és következő
      lépését jelentettem.

## Megfelelő hely és elsődleges forrás

- [ ] Az [index](../index.md) alapján a megfelelő réteget választottam:
      tartós projektszabály a [steering](../steering/), indokolt döntés az
      [ADR-ek](../decisions/), domainismeret a [knowledge](../knowledge/),
      ismételhető eljárás a [playbooks](../playbooks/), teljes folyamat a
      [workflows](../workflows/) alá tartozik.
- [ ] Újrahasználható AI-utasítás a [prompts](../prompts/), fájlminta a
      [templates](../templates/), ellenőrzőlista a [checklists](./), tartós
      tanulság a [memory](../memory/) helyére került. Olvasóknak szóló termék-,
      API-, fejlesztői és felhasználói útmutató a [docs](../../docs/) alá tartozik.
- [ ] A README, AGENTS és más belépési pont csak valódi tartalmi vagy navigációs
      változás miatt érintett. Nem frissítettem minden réteget automatikusan.
      A részletes szabályt az elsődleges forrásra mutató linkkel használom;
      nincs második szabályzat, párhuzamos fogalomtár vagy ismételt tesztmátrix.

## Érthetőség, nyelv és példák

- [ ] A [kódolási stílus dokumentációs szabályai](../steering/coding-style.md#documentation-language-and-readability)
      szerint előbb egyszerűen leírtam, mit teszünk és miért, majd a technikai működést.
- [ ] Az üzletet ismerő, nem fejlesztő olvasó a dokumentum típusához illően
      megértheti a problémát, a rendszer válaszát, a döntés okát, a változó és
      változatlan részeket, valamint egy tipikus példát. Nem minden dokumentumhoz
      kell mind a hat kérdést mechanikusan megválaszolni.
- [ ] A magyar magyarázat természetes, rövid és világos; nincs indokolatlan
      magyar–angol keverés. A [terminológiai standard](../knowledge/domain-terminology.md)
      szerinti hivatalos fogalom és rövidítés az első érdemi előforduláskor
      érthetően bevezetett. Ugyanazt az azonosítót nem fordítom újra meg újra.
- [ ] A szükséges példa életszerű, érthető és egyezik a szabállyal. A minta
      megkülönböztethető a tényleges futási bizonyítéktól; nincs benne titok,
      személyes vagy valódi üzleti adat.

## Technikai pontosság és időbeli érvényesség

- [ ] Az osztály-, metódus-, mező-, adatbázis-, enum-, route- és permissionnevek,
      parancsok és fordítási kulcsok pontosak. Az olyan azonosítók, mint
      `PurchaseOrder`, `PurchaseOrderDispatch`, `SupplierAcknowledgement`,
      `idempotency_key`, `PASSED`, `FAILED`, `BLOCKED`, `NOT RUN` nem lettek
      lefordítva vagy átnevezve.
- [ ] A fogalmi különbségek, üzleti alapfeltételek, mennyiség- és időjelentések
      megmaradtak a terminológiai standard szerint. A közérthetőbb szöveg nem
      mos össze igényt, szükségletet, hiányt, javaslatot, jóváhagyást,
      végrehajtást és tényleges eredményt.
- [ ] A jelenlegi működés állítását kód, konfiguráció vagy aktuális vizsgálat
      támasztja alá. Az elfogadott szabály, tervezett cél, megvalósítás és
      bizonyíték elkülönül; az ismert szabály–megvalósítás eltérés kifejezetten jelölt.
- [ ] A történeti adat dátuma és környezete világos. Régi tesztszám nem mai
      teszteredmény, régi csomagverzió nem jelenlegi függőség, régi akadály nem
      mai akadály, régi ütemterv nem aktuális következő lépés. Hasznos történeti
      feljegyzést nem töröltem pusztán a kora miatt.
- [ ] A helyi linkek, címsorhivatkozások, fájlutak és példák ellenőrzöttek.
      A parancsok az aktuális scriptekkel és előfeltételekkel egyeznek; nincs
      kitalált parancs, elavult útvonal vagy indokolatlan ismétlés.

## Arányos ellenőrzés és átadás

- [ ] Csak a szándékolt dokumentumokat formáztam és ellenőriztem Prettierrel,
      ahol alkalmazandó. A Markdown- és linkvizsgálatot az elérhető eszközökkel
      vagy külön ellenőrzéssel elvégeztem; a `git diff --check` eredménye rögzített.
      A `npm run format:check` nem ellenőriz Markdown-fájlokat.
- [ ] Csak dokumentációs változásra nem írtam elő automatikus alkalmazástesztet,
      statikus elemzést, buildet, E2E-t vagy Full futást, merge előtt sem.
      Alkalmazás-/eszközműködés változása vagy más alkalmazandó szabály esetén
      a DoD és a rétegezett útmutató szerinti ellenőrzést választottam.
- [ ] Átnéztem a teljes saját diffet, a szóhasználatot és a dokumentumok közötti
      összhangot. Idegen változást megőriztem; a lista nem ad engedélyt annak
      formázására, stagingjére vagy commitjára.
- [ ] A jelentésben tényleges parancs/lépés, dátum vagy futásazonosító,
      környezet és DoD szerinti eredmény szerepel. Minden `FAILED`, `BLOCKED`,
      `NOT RUN` tétel oka, hatása, felelőse és következő lépése látható.
      Leírt ok nem `PASSED`; rendezetlen kötelező hiány mellett nem állítok
      teljes készültséget.

Elfogadott ADR nem bizonyít implementációt. Dokumentált készültség és
README-állítás nem validálási bizonyíték; frissített dokumentáció nem
kijavított alkalmazás. A lista teljesítése kizárólag a megvizsgált
dokumentációról ad igazolást, nem az alkalmazás helyességéről vagy Git-művelet
engedélyezéséről. A [commit előtti](before-commit.md),
[merge előtti](before-merge.md) és
[code review](../../docs/project-management/code-review-guide.md) eljárásoknak
megmarad a saját szerepük.
