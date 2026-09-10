# KM_Production következő lépései

## Mire való ez a lista?

Ez a dokumentum a [backlog](backlog.md) tíz kiemelt tételéhez ad következő
konkrét lépést. Megőrzi a korábbi sorrendet; nem második backlog, és nem
állítja, hogy minden függőség már teljesült. A részletes tartalom, prioritás,
elfogadási feltétel és munkaállapot a backlogban marad.

- Kiinduló terv: 2026-07-28.
- Dokumentációs felülvizsgálat: 2026-09-09–2026-09-10; nem új alkalmazás- vagy CI-mérés.
- Frissítés: a kiemelt tétel, következő lépés, függőség vagy prioritás változásakor.

A „következő lépés” a következő hasznos teendő. Nem jóváhagyás, nem
merge-ready, `done` vagy release-ready állapot. A listából való kimaradás sem
jelent lezárást. A készültséget a [Definition of Done](definition-of-done.md),
a vizsgálatok körét a [rétegezett útmutató](../development/quality-gates.md)
határozza meg. Az [AGENTS.md](../../AGENTS.md) szerinti felhatalmazást ez a terv
nem helyettesíti; nem indít automatikusan implementációt vagy Git/PR/kiadási műveletet.

## Kiemelt következő lépések

Az alábbi pontoknál az igazolás az adott következő lépés eredménye; a teljes
backlogtétel lezárásához az összes alkalmazandó DoD-követelmény rendezése kell.

### 1. CI-002 — A lezárási bizonyíték egyeztetése

- **Következő lépés:** A feladat felelőse keresse meg a `done`-ra állítás
  alapjául szolgáló teljes CI-eredményt, és egyeztesse a backlog állapotát.
- **Miért most:** A backlog `done`, de az eredményszöveg és a korábbi terv
  `review` értéket mond. A három frontend céljob implementált; a teljes
  workflow akkori auditja sikertelen volt. A mai állapot ebből nem következik.
- **Előfeltétel:** `CI-001`; a lezáró CI-bizonyíték és a `CI-007` alatt kezelt
  auditok eredményének azonosítása. A tétel addig nem igazolt lezárt előfeltétel.
- **Igazolás:** Az ellentmondást feloldó, változathoz kötött bizonyíték és
  egyértelmű DoD-értékelés. Részletek: [CI-002 a backlogban](backlog.md#ci-002--frontend-unit-i18n-és-build-quality-gate-stabilizálása).

### 2. CI-004 — A hiányzó Linux E2E-igazolás rendezése

- **Következő lépés:** Keress vissza a változáshoz tartozó Linux CI-futást;
  ha nincs megfelelő bizonyíték, az engedélyezett eljárásban pótold a szükséges
  projektkört. Felelős a tétel végrehajtója.
- **Miért most:** A megvalósítás létezik, a munkaállapot `review`. A korábbi
  „partially done” nem hivatalos állapot; az auditban a CI-futás elmaradt.
- **Előfeltétel:** Izolált E2E-adatbázis, build, böngészők és szükséges hozzáférés
  a [böngészős eljárás](../e2e-testing.md) szerint.
- **Igazolás:** A konfigurált projektek szükséges tesztkörének eredménye és a
  helyi Firefox-korlátot rendező bizonyíték. Leírt kivétel önmagában nem készültség.
  Részletek és történeti forrás: [CI-004](backlog.md#ci-004--a-teljes-playwright-e2e-kapu-aktuális-futtatása).

### 3. CI-005 — A tényleges required check mátrix felmérése

- **Következő lépés:** Vesd össze a workflow-k jobjait a tényleges GitHub
  required-check beállításokkal, és rögzítsd a hiányokat vagy döntéseket.
- **Miért most:** A workflow-fájl nem bizonyít merge-védelmet.
- **Előfeltétel:** `CI-002`, `CI-003`, `CI-004`; a nyitott tételek rendezése és
  a GitHub-beállítások olvasási hozzáférése.
- **Igazolás:** Ellenőrzött job/trigger/required-check mátrix a
  [CI-005](backlog.md#ci-005--github-actions-quality-gate-és-required-check-mátrix-auditja) feltételeivel. A védelmek beállítása a `GOV-005` külön munkája.

### 4. GOV-005 — A jóváhagyott ág-védelmek beállítása

- **Következő lépés:** A mátrix alapján készíts konkrét beállítási javaslatot;
  az engedélyezett módosítás után igazold a védelmek működését.
- **Miért most:** A `main` védelmének az elfogadott review- és DoD-szabályokat
  kell érvényesítenie, nem pusztán workflow-neveket felsorolnia.
- **Előfeltétel:** `GOV-006`, `GOV-008`, `CI-005`, továbbá a szükséges
  adminisztrátori felhatalmazás. A backlogállapot továbbra is `planned`.
- **Igazolás:** Dokumentált beállítások, bypass-döntés és próbák a
  [GOV-005](backlog.md#gov-005--branch-protection-és-required-check-szabályok-bevezetése) szerint; a jelen terv nem állít működő védelmet.

### 5. CI-006 — Composer-audit és CI-döntés

- **Következő lépés:** Rögzítsd az adott változat auditját, rendezd a találatokat
  a DoD szerint, és készíts végrehajtható döntést a CI-be kapcsolásról.
- **Miért most:** A helyi Full tartalmaz Composer-auditot; a backend workflow
  továbbra sem futtatja. A korábbi függőségjavítás nem mai audit-siker.
- **Előfeltétel:** Nincs backlogfüggőség; auditálható függőségállapot és
  elérhető auditforrás szükséges.
- **Igazolás:** Eredmény, találatkezelés és CI-döntés a [CI-006](backlog.md#ci-006--composer-security-audit-release-kapu-igazolása)
  szerint. Tiltott advisory esetén az audit `FAILED`, nem `BLOCKED`.

### 6. CI-007 — A két npm audit eredményének külön értékelése

- **Következő lépés:** Ellenőrizd a teljes és a termelési függőségi auditot,
  továbbá hogy egy korábbi hiba miatt melyik lépés maradt ki a CI-ben.
- **Miért most:** A DoD már szabályozza az eredményeket és kivételeket;
  az adott találatokhoz felelős, döntés és következő lépés kell.
- **Előfeltétel:** Nincs backlogfüggőség; a vizsgált lockfile és az auditforrás
  elérhetősége szükséges. A 2026-07-28-i hibát ne kezeld automatikusan mai hibaként.
- **Igazolás:** Két külön audit-eredmény és rendezett találatok a
  [CI-007](backlog.md#ci-007--npm-security-audit-release-kapu-felülvizsgálata) szerint. El nem indított későbbi lépés `NOT RUN`.

### 7. CI-009 — Egy verziójelölt kiadási bizonyítékának összeállítása

- **Következő lépés:** Egy kijelölt verzióhoz rendeld hozzá az alkalmazandó
  ellenőrzések, visszaállítási terv és jóváhagyások hivatkozásait.
- **Miért most:** A kiadási útmutatók már összekapcsoltak; egy konkrét
  verziójelölt végrehajtási bizonyítéka ettől még külön feladat.
- **Előfeltétel:** `CI-002`, `CI-003`, `CI-004`, `CI-005`, `CI-006`, `CI-007`,
  `CI-010`. A tétel `planned`; a sorrend nem teszi előfeltétel nélkül indíthatóvá.
- **Igazolás:** Visszakereshető, alkalmazhatóság szerint teljes bizonyíték a
  [CI-009](backlog.md#ci-009--egységes-release-gate-és-evidence-csomag) és a DoD alapján; a kimaradt kötelező vizsgálat nem siker.

### 8. OPS-003 — Mentési és helyreállítási célok jóváhagyása

- **Következő lépés:** A domain owner és az üzemeltetés rögzítse a mentendő
  adatokat, elfogadható adatvesztést (RPO), helyreállítási időt (RTO), megőrzést
  és felelőst; egyeztessék az adatbázis és dokumentumtár konzisztens mentését.
- **Miért most:** A telepítési útmutató nem helyettesíti a célkörnyezethez
  jóváhagyott mentési tervet.
- **Előfeltétel:** Nincs backlogfüggőség; a tényleges üzemeltetési döntéshozók
  bevonása szükséges.
- **Igazolás:** Jóváhagyott célok és a tervezett veszteségi esetek értékelése a
  [OPS-003](backlog.md#ops-003--backup-policy-scope-rpo-és-rto-jóváhagyása) szerint. Ez még nem az `OPS-005` helyreállítási próbája.

### 9. LC-001 — A Learning Center v1.0 tartalmának lezárása

- **Következő lépés:** Egyeztesd a specifikáció nyitott kérdéseit, a kötelező
  képességeket, szerepköröket és első támogatott oldalakat.
- **Miért most:** Az adatmodell és UI a jóváhagyott v1.0 tartalomra épülhet.
- **Előfeltétel:** Nincs backlogfüggőség; a termék- és domain-döntés szükséges.
- **Igazolás:** Jóváhagyott döntések, nem célok és megfigyelhető sikerkritériumok
  a [LC-001](backlog.md#lc-001--learning-center-v10-scope-lezárása) szerint. A [roadmap](../specifications/learning-center/roadmap.md)
  megléte önmagában nem implementáció vagy lezárás.

### 10. OPS-001 — Az éles queue-workerek működésének igazolása

- **Következő lépés:** Az üzemeltetés mérje fel a célkörnyezet indítási,
  újraindítási, leállítási és hibából helyreállási eljárását, majd igazolja a
  timeout/retry és ismételt végrehajtás biztonságát.
- **Miért most:** A queue és AI job létezik; az éles környezet működési
  bizonyítékát nem helyettesíti az általános [telepítési útmutató](../deployment.md).
- **Előfeltétel:** Nincs backlogfüggőség; az érintett környezet és felelős
  hozzáférése szükséges.
- **Igazolás:** Környezethez kötött eljárás és próbák a [OPS-001](backlog.md#ops-001--queue-konfiguráció-és-worker-lifecycle-audit) szerint.

## Történeti és megvalósítási kontextus

A backlogban `GOV-002`, `GOV-004`, `GOV-006`, `GOV-008`, `CI-001` és `CI-003`
lezárt előzményként szerepel. Az akkori eredmények ott találhatók; nem mai
GitHub-beállítások vagy aktuális változásra futtatott ellenőrzések.
A [CI-003 audit](../audits/backend-mysql-quality-gates-2026-07-28.md) a
2026-07-29-i négy sikeres backend jobot rögzíti, ezért újraindítandó fejlesztési
feladatként nem szerepel a sorrendben.

Az `AUD-001` a korábbi tervben a backlogtól eltérő, `done` állapottal szerepelt.
A backlog `review` jelölését őrizzük meg; a lezárás bizonyítéka egyeztetendő.
A helyi sikert és a nem hitelesített
CI-futást a [naplózási audit](../audits/record-state-activity-logging-2026-07-29.md)
választja szét. Ez nem új prioritás: a nyitott lezárási kérdés a backlogban marad.
A `CACHE-001` ugyancsak `review`; a nyitott ellenőrzéseket nem zárja le, hogy nincs a tízes listán.

A 0014 és 0015 megvalósítása létezik. A 0016 dispatch/visszaigazolás kódja és
tesztjei is bekerültek, bár az ADR állapotfejléce még implementációra várást
jelez. A részletes források és ez az eltérés a
[backlog MRP-kontextusában](backlog.md#mrp-és-beszerzés-megvalósítási-kontextus) találhatók. Ezért ezek újraimplementálása
nem következő lépés. Az ADR állapotának egyeztetése és az alkalmazandó
bizonyíték felülvizsgálata külön munka; későbbi roadmap nem minősül késznek.

## Következő listafrissítés

A korábban megnevezett következő jelölt `OPS-002`, az ütemezett feladatok és
futtatásuk felmérése. Beemelés előtt a backlog függőségeit, az aktuális
előfeltételeket és a megmaradt tételsorrendet kell egyeztetni. Ez nem
felhatalmazás az ütemezés vagy az alkalmazás automatikus módosítására.
