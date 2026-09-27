# SupplierOptionService – input/output contract v0.1

Dátum: 2026-09-27. **Tervezett szerződés, nem implementált API.** A jelenlegi domain bizonyítékait, Git/security állapotát és korlátait a [procurement input audit](procurement-input-audit.md) rögzíti. A [Merlin baseline](README.md) felelősségi határai kötelezők.

## 1. Felelősség és olvasási határ

A SupplierOptionService AI-, Merlin- és Problem Case-független application/domain service. Egy vásárolt cikk igazolt beszerzési alapigényéhez ismert supplier source-okat és determinisztikusan megállapítható feltételeket ad vissza. A tool később ehhez adapter lesz.

A service nem végez stock/reservation/incoming/netting számítást; nem választ/rangsorol beszállítót; nem hoz létre proposal/PR/PO-t; nem foglal vagy módosít készletet; nem módosít relationshipet, auditlogot vagy cache-t. Az eredmény nem foglal kapacitást és nem jogosít executionre. A későbbi író workflow a normál domain validálást és authorizationt ismét elvégzi.

Első scope: egyetlen `ItemType::PurchasedMaterial`, `purchased_material`. Ez explicit új service-határ, nem a meglévő strict eligibility query szabályának módosítása. Inaktív cikk ismert forrásai továbbra is bemutathatók a jogosult hívónak, de nem eligible-ek. Nem létező vagy soft-deleted cikk, illetve más item type bemeneti elutasítás.

## 2. ProcurementRequirementInput

A SupplierOptionQuery egyetlen immutable ProcurementRequirementInputot hordozzon. Az elnevezések tervezett DTO/API nevek, nem létező DB-oszlopok. A service kizárólag trusted application caller által előállított inputot fogad; egy szabadon kitölthető provenance objektum önmagában nem authority.

| Mező                      | Típus és kötelezőség                                                  | Authoritative source / object / field                                                         | Szemantika, lifecycle, ok                                                                                                                                   |
| ------------------------- | --------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------- |
| item_id                   | Pozitív integer, kötelező                                             | Netting result.requiredItemId; vagy SupplyProposal.item_id; vagy PR item.item_id              | Az értékelt beszerzési cikk azonosítója. A forrásobjektummal és az olvasott Itemmel egyezzen.                                                               |
| required_quantity         | Decimal string, kötelező; kanonikus 3 tizedes, 0..999999999999999.999 | Netting result.netRequirement; vagy proposal.proposed_quantity; vagy PR item.planned_quantity | Beszerzésként értékelendő alapmennyiség, a provenance quantity_basis szerint. Nem gross/peg/adjusted input.                                                 |
| required_date             | Szigorú YYYY-MM-DD vagy null, a kulcs kötelező                        | Netting result.requiredAt; vagy proposal.required_at; vagy PR header.required_at              | Szükségleti nap. A null megőrzendő, nem today/proposed_supply_at fallback.                                                                                  |
| unit                      | Nem üres string, kötelező                                             | Netting result.unit (MR/BOM snapshot); proposal.unit; PR item.unit                            | Az alapigény egysége. Az aktuális Item.unit értékkel egyezzen. Eltérésnél validation error, nem implicit conversion.                                        |
| evaluation_date           | Szigorú YYYY-MM-DD, kötelező                                          | Trusted application clockból az aktuális üzleti nap                                           | Eligibility/timing számítás bázisa az alkalmazás időzónájában. Nem történeti adat-visszajátszás. A service nem hív implicit today-t helyette.               |
| provenance.source_type    | Kötelező zárt diszkriminátor                                          | Az alábbi mappingben rögzített adaptertípus                                                   | Megkülönbözteti a nettó hiányt, manuális tervet és PR-tervet; nem tetszőleges model class vagy SQL-tábla.                                                   |
| provenance.source_id      | Pozitív integer, kötelező                                             | MaterialRequirement.id / SupplyProposal.id / PurchaseRequisitionItem.id                       | A mennyiségi állítás visszakövethető forrása. Nettingnél requirement ID, nem kitalált netting run ID.                                                       |
| provenance.quantity_basis | Kötelező, source_type-pal kötött érték                                | net_requirement / proposal_planned / pr_planned                                               | A már auditált szemantikai eltérést explicit rögzíti. Ismeretlen vagy hibás párosítás elutasítandó.                                                         |
| provenance.observed_at    | ISO-8601 server timestamp offsettel, kötelező                         | Trusted adapter megfigyelési időpontja                                                        | Új metadata; nem állítja, hogy a source eredetileg ekkor készült vagy ma is változatlan.                                                                    |
| provenance.netting_scope  | Netting source-nál kötelező, máskor null                              | A számítást végző upstream által ismert scope                                                 | `all_requirements` vagy `complete_item_requirements`. Követelmény az összes versengő igény részvétele; nem a selected-only case. Nem persistált domainmező. |

Nincs szabad inputként supplier_id, selected_supplier, ranking preference, stock snapshot vagy case által felülírható quantity/date. A provenance nem a service által végzett új netting eredménye.

### Forrástípusok és mapping

| source_type / quantity_basis                   | Az authority megszerzése                                                                                                              | Egyezések, lifecycle és kizárások                                                                                                                                                                                                                                                 |
| ---------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| material_requirement_netting / net_requirement | A normál MRP/netting read útvonal teljes scope számítása után kiválasztott MaterialRequirementNettingResult. source_id=requirementId. | requiredItemId, unit, requiredAt, netRequirement közvetlen mapping. A caller igazolja a complete scope-ot; nincs single-case supply újrafelhasználás. A netting resultnak nincs saját timestamp/run ID-je, ezért observed_at a caller megfigyelése.                               |
| supply_proposal / proposal_planned             | Létező proposal tervezett mennyisége és dátuma, source_id=proposal.id.                                                                | Nincs MR/netting lineage-állítás. Draft/Proposed/Approved tervértékelés különbözik executiontől. Rejected/Cancelled/consumed proposal nem használható aktív procurement needként; történeti elemzés külön jövőbeli use case. A konkrét lifecycle adapter nincs az első szeletben. |
| purchase_requisition_item / pr_planned         | PR item.planned_quantity + unit/item_id, parent PR.required_at; source_id=PR item.id.                                                 | A parent ID/lifecycle és a proposal source összeg egyezését a későbbi adapter ellenőrzi, ha van source lineage. Manuális/legacy PR-hez nem gyárt nettingeredetet. Terminal/executed PR nem friss procurement need. A konkrét lifecycle adapter nincs az első szeletben.           |

Mindhárom jelentést rögzítjük, mert nincs univerzális Problem Case source. Az első service-szelet typed inputot kezel, nem olvassa vagy módosítja e három workflow-t. A tényleges case, proposal és PR adapterek megírása külön scope; a későbbi integráció előtt teljes lifecycle/permission mapping kell. A netting scope metadata állítását is csak megbízható backend producer adhatja, nem az AI.

### Validáció és hibahatár

A hibás item/quantity/unit/date/provenance input az egész hívást elutasítja; nem adunk „nincs beszállító” üres eredményt helyette. A negatív, túl nagy, exponenciális vagy nem pontosan ábrázolható mennyiség nem kerekíthető hallgatólagosan. A kanonikus input decimal string; nincs float. A meglévő replenishment skálázási/overflow szabálya újrahasználandó; ha a query validáláshoz hozzáférés kell, a privát parser viselkedésmegőrző közös helperbe emelése megengedett, második numerikus algoritmus nem.

Az ISO nap parser nem fogadhat el normalizálással érvénytelen dátumot. A múltbeli required_date megengedett: nem inputhiba, hanem determinisztikusan vizsgálható késés. Az evaluation_date explicit; tesztben rögzített clock adja. Az első adapter élő értékelést szolgál, nem tetszőleges múltbeli master snapshotot.

Egy hibás supplier policy az adott optiont teszi unusable-lé és magyarázatot kap; nem nyeli el a queryhibát. Database/authorization/konzisztencia hiba nem alakítható UNKNOWN üzleti adattá. A supplier repository lekérdezései ugyanazon konzisztens read snapshotra épüljenek; ez olvasási tranzakcióval megoldható, üzleti írás vagy lockForUpdate nélkül. Ismételhetetlen read vagy eltérő strict membership esetén explicit értékelési hiba/újraolvasás, nem hamis eligibility. Az isolation garanciát az implementációban kell bizonyítani.

## 3. get_supplier_options későbbi adapterhatára

A tool számára a case-kontextus authoritative. Az eszköz legfeljebb egy engedélyezett case hivatkozását kapja; előre kötött case mellett üzleti paraméter nélküli lehet. Item/quantity/date/unit/evaluation_date/source ID szabad AI-felülírását nem fogadja el. Eltérő vagy nem engedélyezett context esetén elutasít, nem csendben új case-re vált.

Az adapter a backendből tölti a forrást, ellenőrzi a source lifecycle-t, az aktuális felhasználói jogot és a Case Contextot, előállítja a fenti inputot, majd meghívja a független service-t. Tool assembly és minden execution külön ellenőrzés. A Problem Case/MerlinRun/Tool Registry még nincs implementálva; a jelen szerződés nem állít kész integrációt.

Kereskedelmi adatot tartalmazó teljes result csak `item-suppliers.view` jogosultságot ellenőrző callernek adható tovább. Az első szeletben nincs publikus endpoint/tool és nincs field-level permission bővítés. Amikor endpoint készül, a jogosulatlan közvetlen hozzáférés 403/authorization denial tesztje kötelező. A service AI/case-függetlensége nem authorization-mentesség a hívási határon.

## 4. Reuse map

| Capability                | Existing implementation / szemantika                                                                                          | Direct reuse                                       | Refactor candidate                                        | New logic / adapter                                                                                                                              |
| ------------------------- | ----------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------- | --------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| Known sources             | ItemSupplierRepository::paginateForAdminIndex: ismert kapcsolat, admin szűrés/lapozás                                         | Nem ezt a UI-API-t                                 | Nincs szükség admin flow módosítására                     | knownSourcesForItem(itemId), teljes eager-loaded lista, source eligibility filter nélkül; megmaradt kapcsolat soft-deleted Supplier adataival is |
| Strict eligibility        | ItemSupplierRepository::eligibleForItemsAt; ItemSupplier active/approved/validAt scope; aktív, nem soft-deleted Item/Supplier | Igen, explicit evaluation_date; ID membership      | Strict queryt nem lazítjuk                                | Membership illesztése a known listához                                                                                                           |
| Eligibility reasons       | Jelenleg nincs strukturált resolver                                                                                           | Bool authority a strict query                      | Később közös predicate/resolver csak equivalence teszttel | Diagnostic reason projection ugyanazon snapshotból; nem új eligibility truth                                                                     |
| MOQ                       | ItemSupplier.minimum_order_quantity, calculateQuantity                                                                        | Igen                                               | Nem kell új calculator                                    | DTO mapping; null=nincs MOQ, 0 nem pozitív korlát                                                                                                |
| Order multiple            | ItemSupplier.order_multiple, calculateQuantity                                                                                | Igen                                               | Nem kell új calculator                                    | DTO mapping; null=nincs multiple, 0/negatív invalid                                                                                              |
| Effective order quantity  | PurchaseRequisitionReplenishmentService::calculateQuantity → adjustedQuantity                                                 | Igen, tiszta publikus metódus                      | Külön calculator osztály nem előfeltétel                  | Source item/unit és eligibility ellenőrzése hívás előtt                                                                                          |
| Excess quantity           | Ugyanott → excessQuantity                                                                                                     | Igen                                               | Nem kell                                                  | DTO mapping, ugyanazon base unit                                                                                                                 |
| Strategy                  | Ugyanott exact/moq/order_multiple/moq_and_order_multiple                                                                      | Igen                                               | Nem kell                                                  | Nem szállítói rangsor                                                                                                                            |
| Lead time                 | ItemSupplier.lead_time_days                                                                                                   | Igen, reference metadata                           | Nem kell                                                  | Null/0 külön kezelése                                                                                                                            |
| Expected delivery date    | Readiness::evaluateItem privát evaluation date + lead days                                                                    | Közvetlen publikus reuse nincs                     | Közös tiszta timing helper, readiness is használja        | Nullable, calculated dátum; nincs munkanaptár vagy supplier promise                                                                              |
| Date feasibility          | Ugyanott expected > required late; proposed_supply külön PR szabály                                                           | Teljes readiness service nem alkalmas item-queryre | Ugyanaz a timing extraction                               | on_time/late/unknown/N/A result projection, új blocking szabály nélkül                                                                           |
| Reference price/currency  | ItemSupplier.unit_price/currency, Store/UpdateItemSupplierRequest                                                             | Olvasás és a meglévő adatjelentés igen             | Nem kell                                                  | Páros hiány/inconsistency jelzés; nem currency-catalog vagy confirmed quote                                                                      |
| Estimated reference value | Nincs bizonyított price denominator vagy összértékszabály                                                                     | Nem                                                | Nem puszta refaktorral oldható meg                        | V0.1 UNAVAILABLE; nincs formula vagy FX                                                                                                          |
| Decimal validation        | Replenishment privát toScaledInteger/fromThousandths; result decimal stringek                                                 | calculateQuantity belül igen                       | Inputvalidáláshoz indokolt közös, viselkedésazonos parser | Kanonikus input, unit/provenance/date validáció                                                                                                  |
| Freshness                 | Readiness::replenishmentIsStale csak PR policy snapshotot hasonlít                                                            | Nem általános freshnessként                        | Nem kell v0.1-hez                                         | observed/evaluated/source updated idő megkülönböztetése; TTL nélkül                                                                              |
| Access restriction        | ItemSupplierPolicy::viewAny és meglévő commercial elrejtést védő teszt                                                        | Caller authorizationnél igen                       | Nincs field-policy refaktor                               | Jogosultság nélkül teljes denial; RESTRICTED csak későbbi valódi redakcióval                                                                     |
| Procurement provenance    | MR netting result; proposal; PR planned/source lineage külön                                                                  | Mezőszerinti projection                            | Nincs új netting/proposal workflow                        | Typed ProcurementRequirementInput, source/basis diszkriminátor                                                                                   |

Kódbizonyíték: [ItemSupplierRepository](../../../app/Repositories/Admin/ItemSupplierRepository.php), [ItemSupplier modell](../../../app/Models/ItemSupplier.php), [replenishment service](../../../app/Services/Admin/PurchaseRequisitionReplenishmentService.php), [quantity result](../../../app/Support/Procurement/ReplenishmentQuantityResult.php), [readiness service](../../../app/Services/Admin/PurchaseRequisitionExecutionReadinessService.php), [ItemSupplier request](../../../app/Http/Requests/Admin/StoreItemSupplierRequest.php), [policy](../../../app/Policies/ItemSupplierPolicy.php). Kapcsolódó döntések: [0012](../../../.kiro/decisions/0012-supplier-selection.md), [0013](../../../.kiro/decisions/0013-replenishment-strategies.md), [0014](../../../.kiro/decisions/0014-purchase-requisition-execution-readiness.md).

## 5. Result értékállapot és minősítés

Minden alábbi táblázatsor egy tervezett result leaf mezőt nevez meg; a csoportok objektumok, az options lista. A minősítés a forrásképességre vonatkozik, nem arra, hogy ez a result DTO már létezik.

- **EXISTING**: a domainben már tárolt adat vagy publikus eredmény.
- **DERIVABLE**: bizonyított meglévő adatok egyszerű, determinisztikus projectionje/összesítése.
- **REQUIRES_REFACTOR**: szabály már van, de közös használatához extraction szükséges.
- **MISSING_DOMAIN_CONCEPT**: nincs bizonyított ilyen domainadat/szabály.
- **NOT_RECOMMENDED**: a v0.1 céljával ellentétes; nem kerül resultba.

A természet: **A** = authoritative a megnevezett állításra; **C** = calculated; **R** = reference; **U** = unavailable. Az A nem azt jelenti, hogy beszállítói garancia; például egy persisted active flag authoritative saját masteradatként.

Nullable üzleti értékek reprezentációja egységes `{state, value, reason}` envelope: KNOWN esetén van érték és reason=null; UNKNOWN/RESTRICTED/NOT_APPLICABLE esetén value=null és stabil reason kód. A táblázat leaf neve a logikai értéket jelöli. Kötelező, nem nullable scalarok közvetlen értékek. KNOWN false és KNOWN 0 nem hiányzó adat. UNAVAILABLE a forrásképesség minősítése; wire állapotként UNKNOWN + konkrét ok szerepel, nem külön negyedik null jelentés.

RESTRICTED kizárólag backend authorization/redakció eredménye lehet. A minimum slice jogosultság hiányában elutasítja a teljes hívást, ezért ilyenkor result sincs. A future redakció nem adhat source countot/értéket vagy közvetett hiánylistát olyan adatról, amelynek létét sem láthatja a caller. NOT_APPLICABLE üzletileg nem alkalmazható érték, nem hibaelrejtés.

Az idők jelölése: **Q** = trusted input megfigyelése; **E** = evaluation_date napjára vizsgált élő masteradat; **T** = evaluated_at pillanatában olvasott adat, történeti verziógarancia nélkül; **V** = változatlan szerződésverzió. Minden date ISO nap, timestamp ISO-8601 offsettel. Minden mennyiség decimal string; base quantity 3, factor 6, reference price 4 tizedes.

### 5.1 Item, requirement, értékelés

| Leaf mező                             | Minősítés; természet | Forrás                                | Unit       | Idő / hiánykezelés                                  |
| ------------------------------------- | -------------------- | ------------------------------------- | ---------- | --------------------------------------------------- |
| schema_version                        | DERIVABLE; C         | Konstans 0.1                          | —          | V; kötelező                                         |
| item.id                               | EXISTING; A          | Item.id, validált query egyezés       | ID         | T; nem létező item → híváshiba                      |
| item.item_number                      | EXISTING; A          | Item.item_number                      | —          | T; kötelező masteradat                              |
| item.name                             | EXISTING; A          | Item.name                             | —          | T; kötelező masteradat                              |
| item.item_type                        | EXISTING; A          | Item.item_type                        | enum       | T; csak purchased_material scope                    |
| item.unit                             | EXISTING; A          | Item.unit                             | alapegység | T; hiány/eltérés → híváshiba                        |
| item.is_active                        | EXISTING; A          | Item.is_active                        | bool       | T; false ismert, eligibility false                  |
| requirement.required_quantity         | EXISTING; A          | Input és annak forrása                | item.unit  | Q; kötelező, a basis nem hagyható el                |
| requirement.required_date             | EXISTING; A          | Input és annak forrása                | nap        | Q; null → UNKNOWN/MISSING_REQUIRED_DATE             |
| requirement.unit                      | EXISTING; A          | Validált input snapshot unit          | alapegység | Q/T egyezés szükséges                               |
| requirement.provenance.source_type    | DERIVABLE; A         | Trusted adapter source mapping        | enum       | Q; kötelező                                         |
| requirement.provenance.source_id      | EXISTING; A          | MR / proposal / PR item ID            | ID         | Q; kötelező                                         |
| requirement.provenance.quantity_basis | DERIVABLE; A         | Source mapping                        | enum       | Q; kötelező                                         |
| requirement.provenance.observed_at    | DERIVABLE; A         | Adapter clock                         | timestamp  | Q; nem computed_at helyettesítés                    |
| requirement.provenance.netting_scope  | DERIVABLE; A         | Trusted netting producer scope        | enum       | Q; más source-nál NOT_APPLICABLE/NOT_NETTING_SOURCE |
| evaluation_date                       | DERIVABLE; A         | Explicit trusted business clock input | nap        | E; kötelező                                         |
| evaluated_at                          | DERIVABLE; A         | Service server clock az értékeléskor  | timestamp  | T; nem eligibility nap vagy freshness SLA           |

### 5.2 options[].supplier és relationship

Minden látható known ItemSupplier kapcsolat egy option. A Supplier soft delete nem törli szükségképpen a kapcsolatot: a known read explicit withTrashed betöltéssel őrizze meg a supplier azonosítását, az eligibility viszont false és SUPPLIER_DELETED okú. Fizikailag hiányzó parent integritási hiba. Üres options valóban nulla ismert forrást jelent egy sikeres, jogosult olvasásban. Nincs hallgatólagos limit/pagination-csonkolás, preferáltakra szűrés vagy duplicate option.

| Leaf mező                       | Minősítés; természet | Forrás                          | Unit      | Idő / hiánykezelés                                              |
| ------------------------------- | -------------------- | ------------------------------- | --------- | --------------------------------------------------------------- |
| supplier.id                     | EXISTING; A          | Supplier.id                     | ID        | T; hiányzó kapcsolt master integritási hiba                     |
| supplier.code                   | EXISTING; A          | Supplier.code                   | —         | T                                                               |
| supplier.name                   | EXISTING; A          | Supplier.name                   | —         | T                                                               |
| supplier.is_active              | EXISTING; A          | Supplier.is_active              | bool      | T; false ismert                                                 |
| supplier.deleted_at             | EXISTING; A          | Supplier.deleted_at             | timestamp | T; null → NOT_APPLICABLE/NOT_DELETED; ismert dátum → ineligible |
| relationship.id                 | EXISTING; A          | ItemSupplier.id                 | ID        | T                                                               |
| relationship.supplier_item_code | EXISTING; R          | ItemSupplier.supplier_item_code | —         | T; null → UNKNOWN/NOT_RECORDED                                  |
| relationship.is_active          | EXISTING; A          | ItemSupplier.is_active          | bool      | T                                                               |
| relationship.is_approved        | EXISTING; A          | ItemSupplier.is_approved        | bool      | T                                                               |
| relationship.is_preferred       | EXISTING; R          | ItemSupplier.is_preferred       | bool      | T; nem ajánlás                                                  |
| relationship.priority           | EXISTING; R          | ItemSupplier.priority           | integer   | T; nem score vagy ranking                                       |
| relationship.valid_from         | EXISTING; A          | ItemSupplier.valid_from         | nap       | E; null → NOT_APPLICABLE/UNBOUNDED_START                        |
| relationship.valid_until        | EXISTING; A          | ItemSupplier.valid_until        | nap       | E; null → NOT_APPLICABLE/UNBOUNDED_END                          |
| relationship.updated_at         | EXISTING; A          | ItemSupplier.updated_at         | timestamp | T; nem price_verified_at, hiány esetén UNKNOWN/NOT_RECORDED     |

### 5.3 options[].eligibility és requirement_fit

| Leaf mező                            | Minősítés; természet | Forrás                                                                      | Unit          | Idő / hiánykezelés                                    |
| ------------------------------------ | -------------------- | --------------------------------------------------------------------------- | ------------- | ----------------------------------------------------- |
| eligibility.is_eligible              | DERIVABLE; C         | eligibleForItemsAt ID membership                                            | bool          | E/T; kötelező; nem policy exception                   |
| eligibility.reasons                  | DERIVABLE; C         | Ugyanazon snapshot known flagjei és validity                                | kódlista      | E/T; eligible esetén üres; mismatch → értékelési hiba |
| requirement_fit.is_usable            | DERIVABLE; C         | Strict eligibility + calculateQuantity validáció + source/item/unit egyezés | bool          | E/Q/T; kötelező                                       |
| requirement_fit.objectively_feasible | DERIVABLE; C         | Lent rögzített quantity/date truth table                                    | bool envelope | E/Q/T; UNKNOWN vagy NOT_APPLICABLE is lehet           |
| requirement_fit.scope                | DERIVABLE; C         | Konstans quantity_and_date                                                  | —             | V; nem capacity/quality/price garancia                |
| requirement_fit.reasons              | DERIVABLE; C         | Validáció és timing eredmény                                                | kódlista      | E/Q/T; nem pontszám                                   |

Tervezett diagnostic eligibility kódok: ITEM_INACTIVE, SUPPLIER_INACTIVE, SUPPLIER_DELETED, SOURCE_INACTIVE, SOURCE_NOT_APPROVED, BEFORE_VALID_FROM, AFTER_VALID_UNTIL. Ezek új result-kódok a meglévő tények megnevezésére, nem új business státuszok. A validity határnap inclusive. Az eligibility boolt nem a reasons.length alapján számítjuk; azt a strict query adja. Ha a diagnostics ellentmond annak, a hívás nem adhat megbízható resultot.

A fit értékelési sorrendje:

1. Ismert ineligibility vagy érvénytelen policy → is_usable=false, objectively_feasible=KNOWN false; a konkrét ok megmarad. Qty/date számítás N/A/UNUSABLE_SOURCE.
2. Usable és required_quantity=0 → calculated adjusted/excess=0, objectively_feasible és date_fit NOT_APPLICABLE/NO_PROCUREMENT_REQUIRED.
3. Usable, pozitív mennyiség és ismert expected/required nap → objectively_feasible=KNOWN(expected<=required); late esetén false.
4. Usable, pozitív mennyiség és hiányzó required_date vagy lead time → objectively_feasible=UNKNOWN a megfelelő hiánykóddal. A késés nem teszi önmagában ineligible-lé a forrást.

### 5.4 options[].ordering és delivery

| Leaf mező                         | Minősítés; természet | Forrás                                         | Unit                           | Idő / hiánykezelés                                         |
| --------------------------------- | -------------------- | ---------------------------------------------- | ------------------------------ | ---------------------------------------------------------- |
| ordering.minimum_order_quantity   | EXISTING; R          | ItemSupplier.minimum_order_quantity            | item.unit, 3dp                 | T; null → NOT_APPLICABLE/NO_MOQ; 0 ismert                  |
| ordering.order_multiple           | EXISTING; R          | ItemSupplier.order_multiple                    | item.unit, 3dp                 | T; null → NOT_APPLICABLE/NO_ORDER_MULTIPLE                 |
| ordering.strategy                 | EXISTING; C          | ReplenishmentQuantityResult.strategy           | enum                           | E/Q/T; unusable → N/A                                      |
| ordering.effective_order_quantity | EXISTING; C          | calculateQuantity.adjustedQuantity             | item.unit, 3dp                 | E/Q/T; unusable → N/A; 0 KNOWN                             |
| ordering.excess_quantity          | EXISTING; C          | calculateQuantity.excessQuantity               | item.unit, 3dp                 | E/Q/T; unusable → N/A; 0 KNOWN                             |
| ordering.purchase_unit            | EXISTING; R          | ItemSupplier.purchase_unit                     | egységkód                      | T; hiány/üres → UNKNOWN/INVALID_POLICY, unusable           |
| ordering.conversion_factor        | EXISTING; R          | ItemSupplier.conversion_factor                 | base unit / purchase unit, 6dp | T; hiány/érvénytelen → UNKNOWN/INVALID_POLICY, unusable    |
| delivery.lead_time_days           | EXISTING; R          | ItemSupplier.lead_time_days                    | naptári nap                    | T; null → UNKNOWN/MISSING_LEAD_TIME, 0 KNOWN               |
| delivery.expected_date            | REQUIRES_REFACTOR; C | Közös helper: evaluation_date + lead_time_days | nap                            | E/T; unusable/0 need → N/A; null lead → UNKNOWN            |
| delivery.date_fit                 | REQUIRES_REFACTOR; C | Ugyanaz a helper, expected <= required         | on_time / late envelope        | E/Q/T; hiányzó dátum/lead → UNKNOWN; unusable/0 need → N/A |

Az [0013 ADR](../../../.kiro/decisions/0013-replenishment-strategies.md) szerint 1 purchase_unit = conversion_factor × Item base unit. A calculated order mennyiségek **base unitban maradnak**. Nem osztjuk/szorozzuk őket factorral; nincs purchase-unit order quantity kimenet. A strategy értékei a meglévő algoritmusból jönnek: exact, moq, order_multiple, moq_and_order_multiple.

A delivery.expected_date mindig számított becslés. Nem supply_proposal.proposed_supply_at és nem PO.expected_delivery_date. V0.1 nem tartalmaz promised_delivery_date mezőt. Ha később munkanaptár vagy supplier-confirmed promise kell, az külön domainképesség.

### 5.5 options[].commercial és data_quality

| Leaf mező                            | Minősítés; természet      | Forrás                                                            | Unit                                               | Idő / hiánykezelés                                                         |
| ------------------------------------ | ------------------------- | ----------------------------------------------------------------- | -------------------------------------------------- | -------------------------------------------------------------------------- |
| commercial.reference_unit_price      | EXISTING; R               | ItemSupplier.unit_price                                           | currency / **nem igazolt ár-bázisegység**, 4dp     | T; null → UNKNOWN/MISSING_REFERENCE_PRICE; 0 KNOWN; nem confirmed          |
| commercial.currency                  | EXISTING; R               | ItemSupplier.currency                                             | 3 betűs tárolt kód                                 | T; null → UNKNOWN/MISSING_CURRENCY; nincs FX/currency catalog állítás      |
| commercial.price_basis               | MISSING_DOMAIN_CONCEPT; U | Nincs explicit bizonyított nevező a domainben                     | base vagy purchase unit nem választható önkényesen | UNKNOWN/PRICE_BASIS_UNDEFINED                                              |
| commercial.estimated_reference_value | MISSING_DOMAIN_CONCEPT; U | Nincs biztonságos domainformula az ismeretlen price_basis mellett | pénzösszeg nem számítható                          | UNKNOWN/PRICE_BASIS_UNDEFINED; 0 need esetén N/A/NO_PROCUREMENT_REQUIRED   |
| data_quality.missing                 | DERIVABLE; C              | Látható required/lead/price/currency hiányok                      | field path + reason kód lista                      | Q/T; nem teljeskörű supplier-risk értékelés                                |
| data_quality.inconsistent            | DERIVABLE; C              | Hibás source policy vagy price/currency pár inkonzisztenciája     | field path + reason kód lista                      | T; nem nyeli el a query/integritás/snapshot hibát                          |
| data_quality.restricted              | DERIVABLE; C              | Kizárólag tényleges backend redakciós döntés                      | field path lista                                   | A minimum szelet sikeres teljes resultjában üres; deny esetén nincs result |

A raw known reference metadata ineligible source-nál is látható a jogosult callernek; ettől még nincs kiszámított rendelési ajánlat. Hiányzó ár és pénznem nem eligibility-feltétel, és nem akadályozza a quantity/date fitet. Price/currency félpár inkonzisztencia jelzés, nem FX-pótlás. Az empty options esetet a service nem minősíti system errornak és nem tölti fel kitalált forrásokkal.

### 5.6 Szándékosan kizárt mezők

| Mező / képesség                                | Minősítés              | Indok                                                                                       |
| ---------------------------------------------- | ---------------------- | ------------------------------------------------------------------------------------------- |
| supplier-confirmed price / delivery date       | MISSING_DOMAIN_CONCEPT | A reference term és a becslés nem confirmation.                                             |
| fx_rate / converted_total                      | MISSING_DOMAIN_CONCEPT | Nincs vizsgált FX authority, dátum vagy árfolyam-szerződés.                                 |
| price_verified_at / freshness threshold        | MISSING_DOMAIN_CONCEPT | updated_at nem bizonyít ellenőrzési időt; önkényes TTL tilos.                               |
| supplier BLOCKED / QUALITY reason              | MISSING_DOMAIN_CONCEPT | Nem vezetünk be nem létező supplier lifecycle/reason fogalmat.                              |
| ranking / best_supplier / recommendation_score | NOT_RECOMMENDED        | A tool opciókat tár fel, nem választ vagy rangsorol.                                        |
| risk_score / AI confidence percent             | NOT_RECOMMENDED        | Nem következik a meglévő determinisztikus adatokból és nem v0.1 cél.                        |
| advisable / selected_supplier                  | NOT_RECOMMENDED        | Későbbi emberi/Merlin mérlegelés, nem a service számított eredménye.                        |
| global is_stale                                | NOT_RECOMMENDED        | Összemosná az ismert PR policy-snapshot driftet a nem definiált supplier-adatfrissességgel. |

## 6. Minimális következő implementációs szelet

Az alábbi scope **csak új emberi döntés után** indulhat:

1. Immutable ProcurementRequirementInput / SupplierOptionQuery és result DTO-k a fenti decimal/date/provenance/értékállapot szerződéssel. Nincs DB-schema vagy persistált új provenance.
2. ItemSupplier repository interface + implementation knownSourcesForItem olvasás, eager loading, teljes known lista. A strict eligibleForItemsAt változatlan szabályokkal használható.
3. AI/case-független SupplierOptionService, stabil supplier_id/source_id sorrenddel. Ismert vs eligible membership, diagnostics, input/source/unit validálás, read-snapshot konzisztencia. Nincs action vagy stateful workflow hívás.
4. A meglévő publikus calculateQuantity közvetlen reuse-ja. A decimal parser csak akkor kerül közös helperbe, ha a query validálása ezt igényli; viselkedésváltozás nem fér bele.
5. A lead-time dátumszámítás és late összehasonlítás közös tiszta helperbe emelése; a meglévő readiness ezt használja, PR warning/blocker viselkedése megmarad. Nincs új naptárszabály.
6. Célzott tesztek és a backend változásra releváns formatter/static/affected kapuk. Price basis és estimated value továbbra is explicit UNKNOWN; nincs árképlet.

A service inputot fogad; a valódi Problem Case, Supply Proposal és PR context adapterek, a netting futtatásának bekötése, GetSupplierOptionsTool, registry, MerlinRun, orchestrator, OpenAI integráció, IMF UI, Nagy Könyv persistence és emberi action flow külön későbbi szeletek. Nincs új endpoint/permission, supplier selection/ranking, netting/proposal automatizálás, PR/PO írás vagy dependency-frissítés.

### Kötelező acceptance bizonyítékok a következő szeletben

| Viselkedés                  | Szükséges bizonyíték                                                                                                                                                                                                |
| --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Known ≠ eligible            | Inaktív item/supplier/source, soft-deleted supplier, unapproved, expired/future és inclusive bound esetek; known listában megmaradnak, strict membership/reasons egyeznek. Preferred/priority nem tesz eligible-lé. |
| Input authority             | Hibás source/basis, unit, item type, negatív/overflow/precision/date bemenet elutasított; null szükségleti nap megmarad. Konkrét case authorization teszt majd a tool-szeletben.                                    |
| Double-netting kizárása     | Net basis 3 változatlanul jut a calculatorba; nincs stock/reservation/PO/netting olvasás. Ugyanaz a query más készletállapot mellett ugyanazt az opciószámítást adja.                                               |
| Quantity reuse              | Exact, MOQ, multiple, kombinált és nulla igény; decimal edge/overflow. A meglévő replenishment tesztek változatlanul védenek, nincs új képlet.                                                                      |
| Timing reuse                | Same-day, late, null lead, zero lead, null required, múltbeli required; naptári nap viselkedés. Readiness meglévő warning/blocker regresszió.                                                                       |
| Unit correctness            | Különböző purchase_unit/factor mellett a kimenet továbbra is base unit; snapshot/current unit eltérés elutasított.                                                                                                  |
| Kereskedelmi bizonytalanság | Hiányzó/0 ár, hiányzó/félpár currency, ismeretlen price_basis; estimated value nem kitalált szám.                                                                                                                   |
| Read-only és konzisztencia  | Nincs INSERT/UPDATE/DELETE, audit/cache side effect vagy stateful PR hívás; ismételt hívás azonos masteradatokkal azonos üzleti eredményt ad. Known/eligible snapshot drift nem lesz hamis válasz.                  |
| Lista teljessége            | Nincs rejtett limit és N+1; stable ID sorrend, üres ismert lista külön a híváshibától.                                                                                                                              |
| Scope ellenőrzése           | Nem jelenik meg rank, recommendation, score, confirmed adat vagy freshness TTL; authorizationt megkerülő endpoint nincs.                                                                                            |

Az affected kiválasztást az implementáció tényleges diffje dönti el. A MySQL read-snapshot viselkedéshez a guardolt DB elérhetősége szükséges lesz; a jelen ENVIRONMENT_BLOCKED állapot nem írható át PASS-ra. Ha ez akkor releváns ellenőrzést akadályoz, a kód review-ready lehet dokumentált korláttal, de nem nevezhető minden kapun átment vagy merge-ready változásnak.
