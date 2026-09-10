# KM_PRODUCTION — TERMELÉSI TÉRKÉP / OPERATÍV ANYAG- ÉS FOLYAMATÁRAMLÁS

## Context

A KM_Production Laravel + Vue 3 + Inertia + PrimeVue alapú Manufacturing Execution System.

Egy új, grafikus operatív nézetet szeretnénk tervezni, amely egyetlen felületen mutatja meg a teljes aktuális rendelésállomány teljesítéséhez szükséges:

- késztermékeket,
- félkész termékeket,
- alkatrészeket,
- alapanyagokat,
- készleteket,
- hiányokat,
- beszerzéseket,
- gyártási igényeket,
- gyártási folyamatokat,
- fizikai anyagmozgásokat,
- késztermékeket,
- kiszállításokat,
- problémákat és késési kockázatokat.

A munkanév:

**Termelési térkép**

Ez NEM egyszerű dashboard és NEM statikus folyamatábra.

A cél egy interaktív, vizuális, döntéstámogató és operatív vezérlőfelület létrehozása.

Alapelve:

**Lásd → Értsd → Intézkedj**

A felhasználónak lehetőség szerint ugyanazon a felületen kell:

1. felismernie a problémát,
2. megértenie annak okát és következményeit,
3. intézkednie a megoldásáról.

---

# 1. ALAPVETŐ NÉZŐPONT

A rendszer alapnézete NEM egyetlen rendelésből indul ki.

A napi operatív állapothoz az összes releváns, még teljesítendő rendelést együttesen kell figyelembe venni.

Kiindulási kérdés:

> Mit kell a jelenlegi rendelésállomány teljesítéséhez biztosítani, beszerezni, legyártani, mozgatni és kiszállítani?

A rendszer aggregálja a rendelési igényeket.

Példa:

Aktív rendelések
    ↓
Késztermék igény
    ↓
BOM robbantás
    ↓
Félkésztermék / alkatrész / alapanyag igény
    ↓
Készlet és rendelkezésre állás
    ↓
Nettó szükséglet
    ↓
Beszerzés vagy gyártás
    ↓
Gyártási folyamat
    ↓
Anyagmozgatás
    ↓
Késztermék
    ↓
Kiszállítás

A BOM feldolgozása többszintű lehet, ezért az igényfeltárás rekurzív.

---

# 2. AZ MRP MARADJON AZ IGAZSÁG FORRÁSA

A Termelési térkép lehetőség szerint NE hozzon létre saját, párhuzamos MRP/netting logikát.

Használja a projekt meglévő domainmodelljét és szolgáltatásait:

- Customer Orders
- Items
- BOM
- Material Requirements
- Netting
- Requirement Pegging
- Inventory
- Reservations
- Supply Proposals
- Purchase Requisitions
- Purchase Orders
- Supplier acknowledgement / procurement folyamatok
- Production Plans
- Production Tasks
- Operations
- Goods Receipts
- Stock Movements
- és az aktuális projektben ezekhez kapcsolódó további modelleket.

A grafikus nézet elsősorban a meglévő üzleti igazság vizualizációja és operatív kezelőfelülete legyen.

Ha a jelenlegi domainmodellből valamely szükséges kapcsolat vagy információ nem vezethető le megbízhatóan, azt külön GAP-ként kell azonosítani.

---

# 3. KÉSZLET ÉS NETTÓ SZÜKSÉGLET

A nézetnek ne csak a bruttó igényt mutassa.

Példa:

MAT-001

Bruttó igény:          82 kg
Raktárkészlet:         35 kg
Már lefoglalt:         10 kg
Szabad készlet:        25 kg
Várható beérkezés:     20 kg
Fedezet:               45 kg
--------------------------------
Nettó hiány:           37 kg

Gyártott alkatrésznél például:

Bruttó igény:        1 200 db
Szabad készlet:        300 db
Folyamatban gyártva:   500 db
--------------------------------
Gyártandó:             400 db

A rendszer különböztesse meg legalább:

- rendelkezésre áll,
- lefoglalt,
- várható,
- gyártás alatt,
- beszerzés alatt,
- fedezetlen,
- késésveszélyes.

---

# 4. AZ IDŐ DIMENZIÓJA

Nem elegendő azt tudni, hogy valamiből összességében elegendő mennyiség létezik.

A fontos kérdés:

> Akkor és ott rendelkezésre fog állni, amikor szükség van rá?

Ezért a térkép legyen időérzékeny.

Például:

Raktáron: 100 kg

de

Holnap szükséges: 120 kg

eredménye:

Holnapi hiány: 20 kg

Vizsgálandó későbbi lehetőségek:

- Most
- Holnap
- +7 nap
- +30 nap
- egyedi dátum/időszak

A térképnek meg kell tudnia különböztetni a:

- jelenlegi tényállapotot,
- folyamatban lévő eseményeket,
- várható állapotot,
- tervezett jövőbeli állapotot.

---

# 5. GRAFIKUS MEGJELENÍTÉS

A kívánt UX egy modern, zoomolható, mozgatható node/edge alapú hálózati térkép.

Nem klasszikus folyamatábrát szeretnénk.

A nagyobb üzleti területek vizuálisan elkülönülhetnek:

RENDELÉSI IGÉNY
    ↓
ANYAGELLÁTÁS / MRP
    ↓
BESZERZÉS / BELSŐ ELLÁTÁS
    ↓
GYÁRTÁS
    ↓
ANYAGMOZGATÁS
    ↓
KÉSZTERMÉK
    ↓
KISZÁLLÍTÁS

A node-ok kártyaszerű elemek legyenek.

Például:

MAT-001
Kémcső alapanyag

Igény: 62 kg
Fedezet: 25 kg
Hiány: 37 kg

⚠ Anyaghiány

A kapcsolatok is hordozzanak jelentést.

Például különböztessük meg:

- üzleti/logikai kapcsolat,
- tervezett anyagáramlás,
- tényleges fizikai anyagmozgás,
- folyamatban lévő mozgatás.

---

# 6. PROGRESSIVE DISCLOSURE

A teljes rendszer egyszerre történő kirajzolása kerülendő.

Nagy rendelésállomány és többszintű BOM esetén kezelhetetlen „spagetti gráf” keletkezne.

Ezért használjunk fokozatos kibontást.

Alapnézet például:

RENDELÉSÁLLOMÁNY
27 aktív
5 veszélyeztetett
        ↓
ANYAGELLÁTÁS
142 cikk
8 hiány
        ↓
GYÁRTÁSI IGÉNY
38 tétel
4 veszélyeztetett
        ↓
FOLYAMATBAN LÉVŐ GYÁRTÁS
21 feladat
2 probléma
        ↓
KÉSZTERMÉK
        ↓
KISZÁLLÍTÁS

A felhasználó innen drill-down módszerrel haladhasson tovább.

Például:

8 anyaghiány
    ↓
MAT-001 -37 kg
    ↓
Mihez kell?
    ↓
Mely rendeléseket érinti?
    ↓
Mikor szükséges?
    ↓
Mi biztosítja a fedezetet?
    ↓
Van Supply Proposal?
    ↓
Van PR?
    ↓
Van PO?
    ↓
Elküldtük?
    ↓
Visszaigazolta a beszállító?
    ↓
Mikor érkezik?

Alapelv:

**Overview → probléma → ok → üzleti objektum**

---

# 7. SZŰRÉS

Alapértelmezés:

**Összes aktív rendelés – aktuális állapot**

A felhasználó szűrhessen többek között:

- időszak,
- rendelés,
- vevő,
- késztermék,
- cikk,
- üzem,
- raktár,
- művelet,
- állapot,
- probléma,
- késési kockázat szerint.

Kiemelten fontos:

**Csak problémák**

nézet.

Ebben minden normál, megfelelő állapotú ág elrejthető, és csak a beavatkozást igénylő pontok maradnak láthatók.

---

# 8. HIBAOK FELTÁRÁSA

A térkép ne csak azt jelezze, hogy probléma van.

Segítsen megválaszolni:

> Miért?

Példa:

SO-2026-000018 veszélyeztetett
    ↓
Késztermék várhatóan késik
    ↓
Vágás nem kezdhető időben
    ↓
Félkésztermék nem áll rendelkezésre
    ↓
Gyártás anyagra vár
    ↓
MAT-001 hiányzik
    ↓
PO szállítási dátuma későbbi a szükséges dátumnál

A cél egy vizuális ok-okozati lánc létrehozása.

---

# 9. AZONNALI INTÉZKEDÉS

Ez a funkció egyik legfontosabb követelménye.

A probléma felismerése után a felhasználónak lehetőség szerint NE kelljen másik oldalra navigálnia, majd fejben megjegyeznie és újra megadnia a szükséges adatokat.

Alapelv:

**Probléma → releváns intézkedés**

Példa:

MAT-001
Hiány: 37 kg
Szükséges: 2026-09-12
Érintett rendelések: 4

Lehetséges műveletek:

[ Feladat kiadása ]
[ Supply Proposal létrehozása ]
[ Részletek ]

A Feladat kiadása műveletnél a rendszer automatikusan töltse ki a rendelkezésére álló kontextust.

Példa:

Feladat:
MAT-001 beszerzésének intézése

Cikk:
MAT-001

Hiány:
37 kg

Szükséges dátum:
2026-09-12

Érintett rendelések:
SO-...
SO-...
SO-...

Címzett:
Beszerzési osztály

Prioritás:
Magas

A felhasználónak csak a valóban szükséges adatokat kelljen megadnia.

---

# 10. TASK ÉS ÜZLETI WORKFLOW SZÉTVÁLASZTÁSA

A feladatkezelés nem helyettesítheti a tényleges üzleti dokumentumokat és workflow-kat.

Például:

MAT-001 hiány
    │
    ├── Feladat
    │   „Beszerzés intézze”
    │
    └── Üzleti folyamat
        Supply Proposal
            ↓
        Purchase Requisition
            ↓
        Purchase Order
            ↓
        Dispatch
            ↓
        Supplier Acknowledgement
            ↓
        Goods Receipt

A Task a felelősséget és intézkedést kezeli.

Az üzleti dokumentumok továbbra is a tényleges domainfolyamatot reprezentálják.

A kettő összekapcsolható.

Például egy feladathoz később kapcsolódhat:

- Supply Proposal
- Purchase Requisition
- Purchase Order
- Production Plan
- Production Task
- Stock Movement
- egyéb releváns domainobjektum.

---

# 11. KONTEXTUSFÜGGŐ ACTIONÖK

Ne minden problémán ugyanazok a gombok jelenjenek meg.

A rendszer az aktuális állapot alapján kínálja fel az értelmes következő lépéseket.

Példa:

Anyaghiány, még nincs intézkedés:

[ Supply Proposal létrehozása ]
[ Feladat kiadása ]

Supply Proposal már létezik:

[ Beszerzési folyamat folytatása ]
[ Feladat kiadása ]

PO már elküldve, de várhatóan késik:

[ Beszerző értesítése ]
[ Feladat kiadása ]
[ PO megtekintése ]

Gyártási probléma esetén más actionök jelenjenek meg.

Az actionök legyenek domain- és állapotfüggők.

---

# 12. CONTEXT PANEL

A node-ra kattintás lehetőség szerint ne kényszerítse a felhasználót azonnal más oldalra.

Vizsgáld meg egy jobb oldali context panel alkalmazását.

Példa:

┌──────────── TERMELÉSI TÉRKÉP ───────────┬───────────────┐
│                                         │ MAT-001       │
│                                         │               │
│          vizuális hálózat               │ Hiány: 37 kg  │
│                                         │ Kell: 09.12   │
│                                         │               │
│                                         │ 4 rendelés    │
│                                         │               │
│                                         │ [Feladat]     │
│                                         │ [Proposal]    │
│                                         │ [Részletek]   │
└─────────────────────────────────────────┴───────────────┘

A felhasználó így intézkedés közben sem veszíti el a térképi kontextust.

---

# 13. A MODUL FŐ KÉRDÉSEI

A Termelési térkép segítségével gyorsan lehessen megválaszolni:

- Mit kell teljesítenünk?
- Mi szükséges hozzá?
- Mi van raktáron?
- Mi van lefoglalva?
- Mi érkezik?
- Mi hiányzik?
- Mit kell beszerezni?
- Mit kell legyártani?
- Mi van már gyártás alatt?
- Hol található jelenleg az anyag/félkésztermék?
- Mit kell fizikailag mozgatni?
- Hol van elakadás?
- Mi késik?
- Mely rendelések vannak veszélyben?
- Miért vannak veszélyben?
- Ki foglalkozik már a problémával?
- Történt már intézkedés?
- Mi a következő értelmes lépés?
- Mit tudok most azonnal tenni?

---

# 14. FONTOS UX ALAPELV

A képernyő ne adatbázis-entitások vizualizációja legyen.

A felhasználót elsősorban nem az érdekli, hogy mely repository, model vagy service szolgáltatta az adatot.

A képernyő üzleti kérdéseket válaszoljon meg.

Alapelv:

**Observe → Understand → Act**

vagy:

**Lásd → Értsd → Intézkedj**

---

# 15. ELSŐ FELADAT — AUDIT, NEM IMPLEMENTÁCIÓ

Még NE implementálj.

Először auditáld a KM_Production aktuális állapotát.

Olvasd el a projekt releváns dokumentációját, ADR-jeit, domain knowledge fájljait és a kapcsolódó kódot.

Vizsgáld meg különösen:

- Customer Order
- BOM
- Material Requirement
- Netting
- Requirement Pegging
- Inventory / Reservation
- Supply Proposal
- Purchase Requisition
- Purchase Order
- Dispatch
- Supplier Acknowledgement
- Goods Receipt
- Production Plan
- Production Task
- Operation Sequence
- Stock Movement
- Location / Factory Unit
- meglévő task/feladatkezelési lehetőségek
- jogosultságok
- audit logging
- frontend komponensek és vizualizációs lehetőségek.

Az audit során NE feltételezd, hogy a fenti koncepció minden szükséges backend eleme már létezik.

Derítsd ki ténylegesen.

---

# 16. KÉRT AUDIT EREDMÉNY

Az audit végén készíts strukturált jelentést:

## A. Existing capabilities

Mi létezik már, amit közvetlenül fel tudunk használni?

## B. Missing domain capabilities

Mi hiányzik a backend/domainmodellből?

## C. Data relationships

Mely meglévő kapcsolatokból lehet felépíteni:

Order
→ Requirement
→ Supply
→ Production
→ Movement
→ Delivery

láncot?

## D. Traceability gaps

Hol nem tudjuk jelenleg biztosan megválaszolni a:

„Miért?”

és

„Mely rendeléseket érinti?”

kérdéseket?

## E. Task / Action architecture

Van-e megfelelő általános Task rendszer?

Ha nincs, milyen minimális domainmodell lenne indokolt?

NE tervezz automatikusan általános workflow engine-t, ha egyszerűbb megoldás elegendő.

## F. Visualization architecture

Javasolj megfelelő Vue 3 kompatibilis gráf/network megoldást.

Vizsgáld:

- teljesítmény,
- nagy gráf kezelése,
- zoom/pan,
- collapse/expand,
- custom Vue node-ok,
- edge típusok,
- interakció,
- PrimeVue integráció,
- accessibility,
- tesztelhetőség,
- karbantarthatóság szempontjából.

## G. Read model

Vizsgáld meg, szükséges-e külön, optimalizált read model / projection a Termelési térkép számára.

A grafikus felület ne kényszerüljön több száz vagy több ezer külön backend kérésre.

## H. Performance risks

Külön vizsgáld:

- sok rendelés,
- többszintű BOM,
- több ezer requirement,
- nagy készletállomány,
- pegging kapcsolatok,
- sok production task,
- sok stock movement

egyidejű megjelenítésének következményeit.

## I. Permissions

Határozd meg, mely actionök milyen jogosultságokat igényelhetnek.

A vizualizáció megtekintése és az intézkedés végrehajtása legyen külön kezelhető.

## J. Recommended implementation phases

Javasolj kis, biztonságosan implementálható vertikális szeleteket.

Például:

Phase 1
Read-only aggregated production map

Phase 2
Filtering + drill-down

Phase 3
Problem/root-cause visualization

Phase 4
Context panel

Phase 5
Task creation

Phase 6
Context-aware domain actions

Phase 7
Physical material movement visualization

De NE tekintsd ezt kötelező felosztásnak.
Az audit alapján javasolj jobb sorrendet, ha indokolt.

---

# 17. HARD RULES

- Do NOT implement yet.
- Do NOT invent missing domain behavior.
- Do NOT duplicate existing MRP logic.
- Do NOT create a second source of truth.
- Do NOT confuse Task with Purchase Requisition, Purchase Order, Production Task or other business documents.
- Do NOT create a generic workflow engine without demonstrated need.
- Do NOT render the complete database/domain graph.
- Prefer progressive disclosure.
- Prefer existing project conventions.
- Preserve existing domain boundaries.
- Identify uncertainty explicitly.
- Identify missing traceability explicitly.
- Treat performance as a first-class requirement.
- Treat permissions and auditability as first-class requirements.
- Prefer simple business language in user-facing concepts.

Most importantly:

The Termelési térkép must not merely answer:

**„Mi történik?”**

It should progressively answer:

**„Mi történik?”**
→ **„Miért történik?”**
→ **„Mit érint?”**
→ **„Ki foglalkozik vele?”**
→ **„Mit tehetek most?”**

The final goal is:

# Lásd → Értsd → Intézkedj