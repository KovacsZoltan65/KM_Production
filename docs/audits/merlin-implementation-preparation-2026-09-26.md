# Merlin implementáció-előkészítő audit – 2026-09-26

## Eredmény és hatókör

A baseline repositoryban rögzíthető, de a kért SupplierOptionService vertikális szelet **BLOCKED**: a vizsgált mainben nincs ItemSupplier, SupplierSelectionService, Supply Proposal, procurement pegging vagy ADR 0008–0011 lánc. A jelenlegi procurement működés ettől eltérő szerkezetet használ. A hiány nem javítási felhatalmazás és nem bizonyított implementációs hiba.

A [Merlin baseline](../specifications/merlin/README.md) a feladatban átadott elfogadott koncepció lényegét őrzi. Ez az audit a tényleges kódot írja le, és feltételes tervet ad. Nem állítja, hogy az elképzelt procurement domain már létezik.

Auditált kódállapot: f20bf5d23689f0105fc32ad4015dab294ed7b7ad. A kód ebben a menetben változatlan. Az audit az app, database, tests, docs és .kiro forrásait, valamint a Git fájllistát vizsgálta; élő üzleti adatbázist nem olvasott. A megállapítások erre a checkoutra vonatkoznak, nem más branchekre vagy teljes Git-történetre.

## 1. Git state és dokumentáció

- Kiinduló ág: main; kiinduló HEAD: f20bf5d (mentés); tiszta worktree.
- A helyi HEAD, a helyi origin/main és a git ls-remote --heads origin refs/heads/main élő válasza azonos teljes SHA-t adott.
- Létrehozott ág: feature/merlin; létrehozás után az ág és a tiszta worktree külön ellenőrzött.
- Baseline commit: c6619c0, docs: establish Merlin implementation baseline.
- Baseline: docs/specifications/merlin/README.md; navigáció: README.md és .kiro/index.md.
- Az elhelyezés a meglévő modul-specifikációs gyakorlatot követi. Az audit a már használt docs/audits könyvtárba kerül, külön dokumentációs commitban; nem új dokumentációs réteg.
- Nincs push, PR, merge, rebase, reset vagy branch-törlés. A végső auditcommit azonosítóját és a végső Git-állapotot az átadási riport közli, önhivatkozó commitazonosító nem kerül ebbe a fájlba.

## 2. Repository-bizonyítékok

A hivatkozások a fájlokra mutatnak; a metódusnév és a kódállapot együtt azonosítja a vizsgált implementációt.

| Jel | Forrás                                                                                                                                                                                                            | Mit igazol?                                                                                                                    |
| --- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| E1  | [MaterialRequirementService](../../app/Services/Admin/MaterialRequirementService.php), calculateForProductionOrder / calculateBomItem                                                                             | Bruttó igény, készlet/foglalás levonása, persistált hiány; a számítás író és auditáló út.                                      |
| E2  | [MaterialRequirementRepository](../../app/Repositories/Admin/MaterialRequirementRepository.php), updateForProductionOrderBomItem                                                                                  | A rekordkulcs customer_order_item_id + required_item_id; a mezők felülíródnak, production_order_id nincs a kulcsban.           |
| E3  | [MaterialRequirement](../../app/Models/MaterialRequirement.php), [migráció](../../database/migrations/2026_06_21_000022_create_material_requirements_table.php)                                                   | Mennyiségek és unit léteznek, required_date nincs.                                                                             |
| E4  | [PurchaseRequisitionService](../../app/Services/Admin/PurchaseRequisitionService.php), generateFromMaterialRequirements / generatePurchaseOrder                                                                   | Hiányok összevonása cikk és egység szerint, forrásmennyiségek rögzítése, majd PR → PO.                                         |
| E5  | [PurchaseRequisitionRepository](../../app/Repositories/Admin/PurchaseRequisitionRepository.php), missingMaterialRequirements / findForShow                                                                        | Létező read path a még PR-forráshoz nem kötött hiányokra, valamint PR és forrásainak olvasására.                               |
| E6  | [ManufacturingIntelligenceRepository](../../app/Repositories/Admin/ManufacturingIntelligenceRepository.php), procurementRecommendations                                                                           | Aggregált hiány mínusz nyitott PO-mennyiség; legfeljebb 100, pozitív ajánlási sor; nincs case-szintű dátum vagy pegging.       |
| E7  | [Supplier](../../app/Models/Supplier.php), [SupplierAdminRepository](../../app/Repositories/Admin/SupplierAdminRepository.php), [contract](../../app/Repositories/Contracts/SupplierAdminRepositoryInterface.php) | Beszállítótörzs is_active mezővel; nincs cikk–beszállító contract vagy strict source eligibility query.                        |
| E8  | [Item](../../app/Models/Item.php), [PurchaseOrderItem](../../app/Models/PurchaseOrderItem.php)                                                                                                                    | Cikk és unit létezik; purchase conversion, source reference price és currency itt nincs.                                       |
| E9  | [PurchaseOrderService](../../app/Services/Admin/PurchaseOrderService.php), create / update                                                                                                                        | expected_delivery_date bemeneti rendelésadat, nem lead-time kalkuláció vagy supplier confirmation.                             |
| E10 | [ManufacturingIntelligenceRepository](../../app/Repositories/Admin/ManufacturingIntelligenceRepository.php), supplierPerformance / productionRisks                                                                | Historikus átlagos szállítási idő és késési darabszám; a risk_score vevői rendelési kockázat, nem supplier risk score.         |
| E11 | [PurchaseRequisitionItemSource](../../app/Models/PurchaseRequisitionItemSource.php), [PurchaseRequisitionItem](../../app/Models/PurchaseRequisitionItem.php)                                                      | Forráskapcsolat és snapshot quantity létezik, de nem Supply Proposal pegging.                                                  |
| E12 | [CustomerOrder](../../app/Models/CustomerOrder.php), [ProductionOrder](../../app/Models/ProductionOrder.php)                                                                                                      | requested_delivery_date és planned_start_date létezik más üzleti jelentéssel; nincs igazolt procurement required_date mapping. |
| E13 | [GeneratePurchaseOrderRequest](../../app/Http/Requests/Admin/GeneratePurchaseOrderRequest.php), [StorePurchaseOrderRequest](../../app/Http/Requests/Admin/StorePurchaseOrderRequest.php)                          | supplier_id létezésellenőrzés; nem ItemSupplier active/approved/validity eligibility.                                          |
| E14 | [InventoryManagementUiTest](../../tests/Feature/InventoryManagementUiTest.php), test_missing_quantity_is_calculated_correctly                                                                                     | A teszt forrása 10 bruttó és 6 készlet mellett 4 hiányt vár. Most nem futtatott teszt.                                         |
| E15 | [ProcurementManagementUiTest](../../tests/Feature/ProcurementManagementUiTest.php), test_identical_items_are_consolidated / test_requisition_preserves_source_links                                               | A teszt forrása az összevonást és a forrásmennyiségeket ellenőrzi. Most nem futtatott teszt.                                   |

Hiányellenőrzés: git ls-files és rg --files alapján a .kiro/decisions könyvtárban 0001–0005 döntés van. A 0008, 0009, 0010, 0011 számozású procurement ADR-ek nem találhatók. A Learning Center saját ADR számai nem procurement ADR-ek. Az app, database, tests, docs és .kiro alatti, kis- és nagybetűtől független keresésben nincs ItemSupplier/item_supplier, SupplierSelectionService, SupplyProposal/supply_proposal, pegging, netting, lead_time_days, unit_price, order_multiple vagy minimum_order_quantity implementáció. A tágabb lead_time/replenishment keresés gyártási mutatókat és általános tudásanyagot talál, nem beszállítói forrásfeltételeket.

## 3. Authoritative procurement requirement

### Tényleges bruttó és nettósítási folyamat

E1 calculateBomItem szerint:

```text
gross = productionOrder.quantity × bomItem.quantity
available = max(0, totalStock - activeReservations)
missing = max(0, gross - reservationsForThisDemand - available)
```

A [StockBalanceRepository::totalQuantityForItem](../../app/Repositories/Admin/StockBalanceRepository.php) a cikk készletét összegzi. A [StockReservationRepository::activeReservedQuantity](../../app/Repositories/Admin/StockReservationRepository.php) aktív foglalásokat olvas, opcionális demand/production szűréssel. E1 nyitott PO-t nem von le, dátum szerinti supply-allokációt nem végez.

A persistált MaterialRequirement.required_quantity **bruttó**, ezért közvetlenül nem a SupplierOptionService.required_quantity forrása. A MaterialRequirement.missing_quantity a jelenlegi készlet/foglalás alapú hiány authoritative mezője, de nem bizonyítottan teljes, időfüggő nettó procurement requirement.

E4 a pozitív, még purchaseRequisitionSources kapcsolattal nem rendelkező hiányokat cikk + unit szerint összevonja:

- PurchaseRequisitionItem.quantity = a csoport missing_quantity összege.
- PurchaseRequisitionItemSource.quantity = az adott forrás missing_quantity értékének létrehozáskori másolata.
- PurchaseOrderItem.ordered_quantity = az igénytétel quantity értéke PO-generáláskor.

A PR.quantity egy konkrét generált beszerzési igény authoritative mennyisége, nem általános, újraszámolt current net requirement. Manuális PR is létrehozható (E4 create), ezért az eredet nélkül nem szabad minden PR-t MRP-eredménynek tekinteni.

### Második, eltérő read path

E6 procurementRecommendations cikkenként összesít, majd levonja a draft/ordered/partially_received PO-k ordered_quantity - received_quantity összegét. A returned recommended_quantity már nyitott PO-val csökkentett ajánlási mennyiség. A [ProcurementRecommendationService](../../app/Services/Admin/ProcurementRecommendationService.php) öt percre cache-eli ezt.

Ez read-only újrahasználható riportképesség, de nem megfelelő authoritative Case-query: nincs requirement ID, required_date, explicit evaluation_date, időablakos coverage vagy mennyiségi pegging; a lista 100 sorra korlátozott és a nulla ajánlás kiesik. A cache TTL nem Merlin freshness-szabály. Nem szabad a riportot módosítás nélkül case-szintű authoritative adapternek nyilvánítani.

### Válasz a mennyiségre és dátumra

| Kérdés                                                         | Audit eredménye                                                                                                                                                                                                                                         |
| -------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Bruttó igény hol keletkezik?                                   | E1 calculateBomItem; persistálás E2, required_quantity.                                                                                                                                                                                                 |
| Hol történik netting?                                          | E1 készlet/foglalás levonása; ettől külön E6 aggregált nyitott PO levonása.                                                                                                                                                                             |
| Mely rekord tárolja a nettót?                                  | MaterialRequirement.missing_quantity tárolja az első hiányt; a generált PR.quantity aggregált igénysnapshot; E6 recommended_quantity csak riporteredmény.                                                                                               |
| SupplierOptionService.required_quantity authoritative forrása? | A kért teljes jelentéssel nem igazolható egyetlen mező. Konkrét PR vizsgálatához PR.quantity, konkrét shortage-hoz missing_quantity használható csak a scope jóváhagyása után; egyik sem nevezhető automatikusan a hiányzó ADR-lánc nettó eredményének. |
| required_date authoritative objektum és mező?                  | MISSING_DOMAIN_CONCEPT a vizsgált requirement/PR láncban. E12 dátumai nem helyettesíthetők automatikusan.                                                                                                                                               |
| Supply Proposal kapcsolat?                                     | Nem található. A PR vagy a riport nem nevezhető át Supply Proposalnak.                                                                                                                                                                                  |
| Pegging kapcsolat?                                             | Nincs a kért domainben. E11 forráskapcsolat a PR-eredetet és snapshot mennyiséget őrzi; nem időfüggő demand–supply allokáció.                                                                                                                           |
| Meglévő megfelelő service?                                     | A fenti részképességek léteznek; a teljes case-szintű quantity/date szerződést egyik sem biztosítja.                                                                                                                                                    |
| Új read service vagy kompozíció?                               | Read adapter/kompozíció csak a requirement scope és dátum tulajdonosának tisztázása után; egy új service önmagában nem oldja meg a hiányzó domainjelentést.                                                                                             |

### Dupla netting elkerülése

A jövőbeli query a már eldöntött quantity-t, unitot és provenance-t vigye át változatlanul. SupplierOptionService nem hívhatja E1 calculateForProductionOrder vagy E4 generateFromMaterialRequirements író metódusait, és nem vonhat le újra stockot, reservationt vagy open PO-t. E6 recommended_quantity esetén különösen hibás lenne az open PO ismételt levonása. A különböző scope-okból származó mennyiségek nem cserélhetők fel.

Külön review-t igényel az E2 rekordkulcsa: több production order ugyanazon customer_order_item_id + required_item_id párra ugyanazt a rekordot frissítheti. Ez statikus kódból látható tulajdonság, nem ebben a menetben reprodukált üzleti hiba. A per-case provenance terv ezt nem hagyhatja figyelmen kívül.

## 4. Reuse / refactor map

| Capability                 | Existing implementation                        | Reuse directly                            | Refactor candidate                                                        | New logic needed                                              |
| -------------------------- | ---------------------------------------------- | ----------------------------------------- | ------------------------------------------------------------------------- | ------------------------------------------------------------- |
| Known supplier sources     | E7 Supplier törzs; nincs ItemSupplier          | Csak supplier-identitás, nem cikkforrás   | Nincs extractálható known-source query                                    | A hiányzó domain alap előbb tisztázandó                       |
| Strict eligibility         | E13 supplier létezésellenőrzés                 | Nem                                       | Nincs a feltételezett strict query                                        | Jóváhagyott procurement szabály szükséges, nem Merlin-szabály |
| Eligibility reason         | Nincs forrásszintű értékelés                   | Nem                                       | Csak később, közös policy/evaluator                                       | A szabály és okkódok hiányoznak                               |
| MOQ/order multiple         | Nincs mező vagy kalkulátor E7/E8-ban           | Nem                                       | Nincs jelenlegi algoritmus, amit ki lehetne emelni                        | Domainfüggőség, külön scope                                   |
| Delivery timing            | E9 rendelési dátum; E10 historikus statisztika | Kontextusként, nem source feasibilityként | Nem azonos a kért lead-time szabállyal                                    | Lead time, naptár és required_date szemantika hiányzik        |
| Reference price/value      | Nincs source unit_price/currency E7/E8-ban     | Nem                                       | Nincs meglévő algoritmus                                                  | Ár, egység, pénznem és kerekítés domainje tisztázandó         |
| Requirement read           | E5 missingMaterialRequirements / findForShow   | Igen, a jelenlegi scope-on belül          | E4 és E5 selection-predikátuma közösíthető a lock/tranzakció megőrzésével | Case mapping és authoritative dátum még hiányzik              |
| Procurement recommendation | E6 és ProcurementRecommendationService         | Riportként igen                           | Case-queryként nem egyszerű extraction                                    | Scope/coverage döntés szükséges; második netting tilos        |

KnownSourcesForItem külön read path indokolt lehet az ismert, de ineligible kapcsolatok megjelenítésére **ha** az ItemSupplier domain rendelkezésre áll. Jelenleg nincs kapcsolati adatmodell, amire ezt meg lehetne írni. Később a strict selector és az okokat szolgáltató evaluator ugyanazt az eligibility szabályt használja; egyezőségi teszt védje a query és az értékelés határértékeit. Ez terv, nem mostani extraction vagy új szabály.

## 5. ItemSupplier szemantikai audit

| Tervezett adat                   | Jelenlegi bizonyíték és minősítés                                                                                                                                  |
| -------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| active                           | Supplier.is_active létezik (E7); nem ItemSupplier.active. MISSING_DOMAIN_CONCEPT forrásszinten.                                                                    |
| approved, preferred, priority    | Nincs cikk–beszállító kapcsolat és ilyen forrásmező (E7/E8, hiányellenőrzés). MISSING_DOMAIN_CONCEPT.                                                              |
| valid_from, valid_until          | Nincs forrásérvényességi intervallum. MISSING_DOMAIN_CONCEPT.                                                                                                      |
| purchase unit, conversion factor | Item.unit és PR/PO unit létezik, de nem igazolt purchase unit vagy konverzió (E8/E11). MISSING_DOMAIN_CONCEPT.                                                     |
| MOQ, order multiple              | Nincs implementáció vagy mező. MISSING_DOMAIN_CONCEPT.                                                                                                             |
| unit_price, currency             | Source reference price és currency nincs a vizsgált procurement domainben. Pontos meglévő unit_price szemantika ezért nem adható meg. MISSING_DOMAIN_CONCEPT.      |
| lead_time_days                   | Nincs ilyen source mező vagy algoritmus. E10 historikus average_delivery_days nem azonos vele. Naptári/munkanap szemantika sem igazolható. MISSING_DOMAIN_CONCEPT. |

## 6. SupplierOptionService contract assessment

A minősítés a javasolt mező **kért szemantikájára** vonatkozik. EXISTING: meglévő adat; DERIVABLE: hiteles bemenetből egyszerűen előállítható; REQUIRES_REFACTOR: létező logika átszervezése szükséges; MISSING_DOMAIN_CONCEPT: a szükséges üzleti fogalom/szemantika nincs; NOT_RECOMMENDED: az adott alak félrevezető lenne. A hiányzó üzleti feltételt nem minősítjük egyszerű refaktornak.

| Mező                                 | Minősítés              | Indok / bizonyíték                                                                                                                                           |
| ------------------------------------ | ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| item                                 | EXISTING               | Item ID/number/name/unit (E8), kapcsolata E3-ban. DTO-projekció még nincs.                                                                                   |
| requirement                          | MISSING_DOMAIN_CONCEPT | A teljes authoritative quantity/date/provenance szerződés nincs (E1–E6/E12); részadatai léteznek.                                                            |
| evaluated_at                         | DERIVABLE              | Technikai kiértékelési időpontból; nem azonos az explicit evaluation_date üzleti referencianappal. Ez javasolt DTO-metaadat, nem DB-mező.                    |
| options[]                            | DERIVABLE              | Eredménykonténerként összeállítható, de tartalma a hiányzó forrásdomain nélkül nem szolgáltatható. Üres lista nem bizonyítaná, hogy nincs ismert beszállító. |
| options[].supplier                   | EXISTING               | Supplier-identitás E7; a cikkhez rendelés hiányzik.                                                                                                          |
| options[].source                     | MISSING_DOMAIN_CONCEPT | ItemSupplier hiányzik.                                                                                                                                       |
| source.item_supplier_id              | MISSING_DOMAIN_CONCEPT | Nincs ilyen kapcsolat/azonosító.                                                                                                                             |
| source.active                        | MISSING_DOMAIN_CONCEPT | Supplier.is_active nem helyettesíti a kapcsolat aktivitását (E7).                                                                                            |
| source.approved                      | MISSING_DOMAIN_CONCEPT | Nincs source approval.                                                                                                                                       |
| source.preferred                     | MISSING_DOMAIN_CONCEPT | Nincs source preference.                                                                                                                                     |
| source.priority                      | MISSING_DOMAIN_CONCEPT | Nincs source priority, rangsor nem található ki.                                                                                                             |
| source.valid_from                    | MISSING_DOMAIN_CONCEPT | Nincs source validity.                                                                                                                                       |
| source.valid_until                   | MISSING_DOMAIN_CONCEPT | Nincs source validity.                                                                                                                                       |
| options[].eligibility                | MISSING_DOMAIN_CONCEPT | Nincs strict eligibility szerződés (E7/E13).                                                                                                                 |
| eligibility.eligible                 | MISSING_DOMAIN_CONCEPT | Létező supplier ID nem elég az eligibilityhez.                                                                                                               |
| eligibility.reasons[]                | MISSING_DOMAIN_CONCEPT | Nincs közös evaluator és domain okkódkészlet.                                                                                                                |
| options[].ordering                   | MISSING_DOMAIN_CONCEPT | Beszállítói rendelési feltételek hiányoznak (E8).                                                                                                            |
| ordering.purchase_unit               | MISSING_DOMAIN_CONCEPT | A meglévő unitból nem állítható, hogy source purchase unit.                                                                                                  |
| ordering.conversion_factor           | MISSING_DOMAIN_CONCEPT | Nincs egységkonverziós szemantika.                                                                                                                           |
| ordering.minimum_order_quantity      | MISSING_DOMAIN_CONCEPT | Nincs MOQ.                                                                                                                                                   |
| ordering.order_multiple              | MISSING_DOMAIN_CONCEPT | Nincs order multiple.                                                                                                                                        |
| ordering.required_quantity           | MISSING_DOMAIN_CONCEPT | A kért authoritative nettó scope nem eldöntött (3. fejezet). Döntés után a bemenet másolata legyen, új netting nélkül.                                       |
| ordering.effective_order_quantity    | MISSING_DOMAIN_CONCEPT | MOQ/multiple/egység szabály nélkül nem vezethető le hitelesen.                                                                                               |
| ordering.excess_quantity             | DERIVABLE              | Feltételes: effective minus required, kizárólag azonos egységben. A szükséges upstream mezők jelenleg blokkoltak.                                            |
| ordering.strategy                    | NOT_RECOMMENDED        | Nincs igazolt strategy domain; ne vezessen be supplier selectiont/rankinget. Csak későbbi, jóváhagyott determinisztikus quantity policy indokolhatja.        |
| options[].delivery                   | MISSING_DOMAIN_CONCEPT | Source timing contract nincs (E9/E10).                                                                                                                       |
| delivery.lead_time_days              | MISSING_DOMAIN_CONCEPT | Historikus átlag nem forrásspecifikus lead time (E10).                                                                                                       |
| delivery.expected_delivery_date      | MISSING_DOMAIN_CONCEPT | PO-n létezik azonos nevű adat (E9), de új option dátuma nem vezethető le belőle.                                                                             |
| delivery.required_date               | MISSING_DOMAIN_CONCEPT | Nincs authoritative procurement dátum (E3/E12).                                                                                                              |
| delivery.feasibility                 | MISSING_DOMAIN_CONCEPT | Timing/calendar/required-date szabály nélkül nem állítható.                                                                                                  |
| options[].commercial                 | MISSING_DOMAIN_CONCEPT | Nincs source árszerződés.                                                                                                                                    |
| commercial.reference_unit_price      | MISSING_DOMAIN_CONCEPT | Nincs unit_price source mező; nem supplier-confirmed price.                                                                                                  |
| commercial.currency                  | MISSING_DOMAIN_CONCEPT | Nincs forráshoz kötött currency (E7/E8).                                                                                                                     |
| commercial.estimated_reference_value | MISSING_DOMAIN_CONCEPT | Ár/áregység/pénznem/kerekítés nélkül a szorzat sem igazolt üzleti érték.                                                                                     |
| options[].missing_information[]      | DERIVABLE              | Explicit hiányjegyzék készíthető a validált eredményből; mi/miért/beszerzés módja. Új DTO-forma, nem létező persisted domain.                                |

A query item_id EXISTING; required_quantity és required_date a fenti okból MISSING_DOMAIN_CONCEPT a kért teljes jelentéssel; evaluation_date explicit új bemeneti szerződés, nem implicit now(). Javasolt a requirement unit és source reference egyértelmű továbbvitele. Case-azonosító a boundaryn maradjon, ne kerüljön az AI-független service-be. Az evaluated_at ne fedje el az evaluation_date értékét; az utóbbit is vissza kell adni a reprodukálhatósághoz.

A REQUIRES_REFACTOR kategória a meglévő E4/E5 query-duplikáció lehetséges rendezésére alkalmazható; a supplier contract hiányzó üzleti mezőit nem lehet puszta refaktorral létrehozni.

### Tiltott feltételezések

Supplier BLOCKED, QUALITY block reason, supplier-confirmed delivery date, supplier-confirmed price, price history, Merlin freshness threshold, AI confidence százalék, supplier ranking és supplier risk score: a kért supplier-option domainben MISSING_DOMAIN_CONCEPT, bevezetésük ebben a szeletben NOT_RECOMMENDED. Az általános AI/OCR confidence guidance nem bizonyít supplier-option confidence adatot. E10 customer-order risk_score és történeti supplier teljesítmény nem supplier risk score vagy rangsor.

## 7. Architecture findings

### BLOCKER

1. A feltételezett ADR 0008–0011 és ItemSupplier/Supply Proposal/pegging implementáció nincs az ellenőrzött mainben. Feloldás: az elvárt procurement alap commitjának/ágának emberi azonosítása, majd külön engedélyezett integráció és új audit; automatikus branchváltás vagy merge nincs.
2. Nem jelölhető ki bizonyított, case-szintű teljes authoritative quantity/date pár (E1–E6/E12). Feloldás: a hiányzó alap auditja vagy külön domain-döntés a quantity scope, date és provenance jelentéséről.
3. Nincs source eligibility, MOQ/multiple, konverzió vagy beszerzési lead-time szabály, amelyre az első tool épülhetne (E7–E10/E13). Nem írható helyettük Merlin-specifikus algoritmus.

### IMPORTANT

- E1, E4 és E6 eltérő scope-ot és coverage-t használ. Összekeverésük dupla nettinghez vagy félrevezető mennyiséghez vezethet; E6 ötperces cache-elt riport.
- E2 rekordkulcsa nem production order szintű; a case-provenance és frissesség ellenőrzésében ezt tisztázni kell. Nincs mellékes javítás.
- E4 és E5 hasonló shortage selectiont tartalmaz; későbbi közösítéskor az író út lockForUpdate és tranzakciója nem veszhet el.
- A régi AI steering általános confidence-követelménye és OCR/Python mintája eltér a Merlin feladat scope-jától. Most az explicit feladat érvényes, később dokumentált hatókör-egyeztetés szükséges; nincs önkényes confidence vagy SDK-választás.
- Az angol régi README/steering mellett a projektkonvenció magyar új dokumentációt ír elő; az új dokumentumok ezt követik, globális fordítás nélkül.

### NICE-TO-HAVE

- A forrásazonosító, quantity unit és evaluation_date jelenjen meg egyértelműen a jövőbeli DTO-ban, hogy E1/E4/E6 scope-ja visszakövethető legyen.
- A baseline és audit kölcsönös linkelése megkönnyíti, hogy a koncepció ne legyen összetéveszthető a megvalósult domainnel.

## 8. Minimális, sorrendezett implementációs terv – még nem végrehajtandó

0. **Előfeltétel:** az elvárt procurement alap azonosítása és a BLOCKER-ek feloldása. Emiatt a megadott vertikális sorrend jelenleg nem indítható közvetlenül. A hiányzó domain felépítése külön scope, nem rejtett Merlin bootstrap munka.
1. **Authoritative requirement:** a tényleges alapból egyetlen, jogosultság- és kontextusellenőrzött read adapter adjon quantity/date/unit/provenance adatot. Null dátum ne kapjon kitalált fallbacket. Bizonyító teszt: a már nettósított mennyiség változatlanul kerül tovább, nincs write és második netting.
2. **SupplierOptionService:** AI-független read service. Known-source repository olvasás és közös strict eligibility értékelés; meglévő quantity/timing kalkulátor kompozíciója vagy viselkedésmegőrző extraction, kizárólag az előfeltétel auditja után. Nem választ és nem rangsorol.
3. **Result DTO:** a 6. fejezet alapján véglegesített szerződés, explicit evaluation_date, mértékegységek, reference ármegnevezés, eligibility okok és information gap. A DTO és service szerződését együtt célszerű tervezni, külön infrastruktúra nélkül.
4. **GetSupplierOptionsTool:** vékony READ adapter; input-validáció és a User Permission ∩ AI Permission ∩ Case Context ellenőrzése. A query authoritative részét a backend oldja fel, az AI nem írhatja át tényként.
5. **Minimális Orchestrator integráció:** csak külön jóváhagyás után; egy futás és egy READ tool út, dupla authorization, strukturált audit, HUMAN_INPUT_REQUIRED és hibatípusok elkülönítése. Autonóm EXECUTE és üzleti írás nincs.

A későbbi tesztek minimuma: jogosulatlan tool assembly/execution; kontextusból kilógó cikk; hiányzó dátum/adat; ismert, de ineligible forrás; strict selector és evaluator egyezése; MOQ/multiple és timing regresszió a hiteles meglévő algoritmushoz; no-write és no-double-netting; nincs ranking. Ezek tervezett tesztek, ebben a dokumentációs menetben nem készültek és nem futottak.

## 9. Ellenőrzések, DoD és korlátok

Változástípus: dokumentáció + engedélyezett Git branch bootstrap. A [Definition of Done](../project-management/definition-of-done.md) dokumentációs, scope-, Git- és bizonyítékkövetelményei alkalmazandók.

Baseline ellenőrzés Windows/PHP 8.4.15 környezetben:

- php tools/quality-gate.php affected --dry-run --explain: csak dokumentációs fast gate, Prettier és git diff --check.
- php tools/quality-gate.php affected: sikeres, 2 parancs. A sandboxos első kísérlet child-process hibával leállt; az engedélyezett környezetben ismételve exit 0.
- git diff --check és git diff --cached --check: sikeres.
- A baseline és a két index 56 relatív linkcélja létezik; hiányzó cél 0.
- Célzott staging, staged névlista/stat/teljes diff kézzel átnézve; csak három Markdown-fájl.

Az audit és a visszalink végső formatter-, link-, whitespace- és staged diff ellenőrzésének eredményét a commit előtti futás és az átadási riport rögzíti. Backend/frontend/E2E, adatbázis-, dependency- és runtime tesztek N/A: ilyen fájl vagy működés nem változik. A hivatkozott domain-tesztek forrását olvastuk, sikeres futásukat nem állítjuk.

A dokumentáció átadható review-ra, de az első implementációs szelet nem ready/done a felsorolt BLOCKER-ek miatt. A teljes eredeti 7. koncepciómentéssel való szó szerinti azonosság nem ellenőrizhető; a feladatban átadott tartalom került rögzítésre. Nincs új elfogadott procurement ADR, kitalált mezőszemantika vagy elhallgatott domainpótlás.

## 10. Explicitly not changed

Production code, controller/service/repository/model, üzleti szabály, készlet, traceability, permission, audit működés, migráció, adatbázis és adat, UI, tesztkód, package/dependency/lockfile, .env, OpenAI SDK, Merlin runtime, Tool Registry és Orchestrator változatlan. A hiányzó domain nem került implementálásra. A talált eltérések dokumentáltak, mellékes javítás nem történt. Nincs push vagy PR.

STOP: emberi review következik. Az implementáció csak külön jóváhagyás után indulhat.
