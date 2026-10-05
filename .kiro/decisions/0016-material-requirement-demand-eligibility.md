# Material Requirement Demand Eligibility és Problem Case Source Validity

- **Állapot:** Elfogadott domain-döntés; nem implementált policy
- **Dátum:** 2026-10-03
- **Ellenőrzött baseline:** `feature/merlin-candidate`, `faeb6f4b4d031a136bcaa6a0dff5c8cdd27b47e5`
- **Kapcsolódó döntések:** [0008.5 MRP Foundation Hardening](0008-5-mrp-foundation-hardening.md), [0009 Material Requirement Netting](0009-material-requirement-netting.md)
- **Kapcsolódó specifikációk:** [Material Shortage Problem Case](../../docs/specifications/merlin/material-shortage-problem-case.md), [Problem Case Foundation Slice 1](../../docs/specifications/merlin/problem-case-foundation-slice-1.md)

## Egyszerű magyarázat

A közös készletért csak aktuális üzleti igény versenyezhet. A történetben
megőrzött anyagszükséglet ettől még olvasható maradhat. Egy igény kizárása nem
bizonyítja, hogy a korábban észlelt hiány megszűnt: a forrás érvényességét és a
hiány aktuális mennyiségét külön kell értékelni.

Ez általános MRP domain policy, nem Merlin-specifikus szűrő. A normál netting és
a későbbi Problem Case Resolver ugyanazt a szabályt használja majd.

## Kontextus és a korábbi döntésekhez való viszony

A baseline [netting repository](../../app/Repositories/Admin/MaterialRequirementNettingRepository.php)
minden nem soft-deleted MaterialRequirementet betölt, rendelési és termelési
lifecycle-szűrés nélkül. A [netting service](../../app/Services/Admin/MaterialRequirementNettingService.php)
ezek `required_quantity` értékéből számol. A jelenlegi működés nem az itt
elfogadott policy implementációja.

Ez az ADR a 0009 demand scope-ját pontosítja és szűkíti. A történeti ADR-t nem
írja át, és nem változtatja meg a quantity-, supply-, sorrend-, unit- vagy
dátumszámítási szabályait. A 0008.5 nullable legacy lineage és calculated status
határa megmarad. A Problem Case specifikáció eddig nyitott forrásérvényességi
határához ez az ADR ad domain-döntést; lifecycle transitiont továbbra sem ad.

## Két külön kérdés

**Netting Demand Eligibility:** jogosult-e ez a MaterialRequirement az aktuális
közös supply poolért versenyezni?

**Problem Case Source Validity:** a Problem Case eredeti MaterialRequirement-alapú
üzleti problémája továbbra is olyan formában létezik-e, hogy current evaluation
végezhető legyen?

A két fogalom kapcsolódik, de nem azonos. Egy bizonytalan lineage miatt kizárt
requirement case-e még értelmezhető lehet, miközben ACTIVE vagy RESOLVED nem
bizonyítható. A történeti elérhetőség nem validity bizonyíték:

> Historical reachability does not imply current demand validity.

## Customer Order

Csak megerősített és még nem terminális Customer Order hozhat létre aktuális
material demandot. A táblázat a Customer Order feltételét adja; a többi source
feltételt is vizsgálni kell.

| CustomerOrder.status   | Demand feltétel | Case source feltétel |
| ---------------------- | --------------- | -------------------- |
| `draft`                | Nem eligible    | Invalid              |
| `confirmed`            | Eligible        | Valid                |
| `material_planning`    | Eligible        | Valid                |
| `waiting_for_material` | Eligible        | Valid                |
| `ready_for_production` | Eligible        | Valid                |
| `in_production`        | Eligible        | Valid                |
| `quality_check`        | Eligible        | Valid                |
| `ready_to_ship`        | Eligible        | Valid                |
| `completed`            | Nem eligible    | Invalid              |
| `cancelled`            | Nem eligible    | Invalid              |

Az értékek forrása a [CustomerOrderStatus enum](../../app/Enums/CustomerOrderStatus.php).

## Customer Order Item

A parent és item lifecycle jelenleg nincs megbízhatóan szinkronizálva, ezért
`planned` vagy későbbi állapotot nem követelünk meg.

- `completed`, `cancelled`: demand nem eligible, source invalid.
- `draft`, `planned`, `waiting_for_material`, `ready_for_production`,
  `in_production`: önmagukban nem zárják ki a demandot; a parent Customer Order
  policy továbbra is érvényes.
- Soft-deleted item: demand nem eligible, source invalid.

Az értékek forrása a [CustomerOrderItemStatus enum](../../app/Enums/CustomerOrderItemStatus.php).
A megerősített parent és Draft item kombinációja önmagában nem lifecycle
ellentmondás és nem kizárási ok.

## Production Order

Ha van production lineage, annak lifecycle-feltételét is alkalmazni kell:

| ProductionOrder.status | Production lifecycle szerinti feltétel | Case source feltétel   |
| ---------------------- | -------------------------------------- | ---------------------- |
| `planned`              | Eligible                               | Önmagában nem zárja ki |
| `released`             | Eligible                               | Önmagában nem zárja ki |
| `in_progress`          | Eligible                               | Önmagában nem zárja ki |
| `waiting_for_check`    | Eligible                               | Önmagában nem zárja ki |
| `completed`            | Nem eligible                           | Invalid                |
| `cancelled`            | Nem eligible                           | Invalid                |

Soft-deleted Production Order esetén demand nem eligible, source invalid.
Az értékek forrása a [ProductionOrderStatus enum](../../app/Enums/ProductionOrderStatus.php).

## Production lineage és bizonytalanság

Teljes, konzisztens production lineage mellett normál lifecycle evaluation
végezhető. A kapcsolt Production Order és BOM Item, valamint a requirement
Customer Order Item és required Item azonosítói nem mondhatnak ellent egymásnak.

**Teljesen hiányzó production lineage:** a `production_order_id` és `bom_item_id`
egyaránt null. A séma és a 0008.5 migrációs története szerint ez legacy/unattributed
requirement lehet. Ha a Customer Order és Customer Order Item valid, és maga a
MaterialRequirement current, a teljesen hiányzó production lineage önmagában
nem zárja ki a demandot vagy a case source-ot. A provenance jelezze a
legacy/unattributed állapotot. Ez kompatibilitási szabály, nem új production
nélküli demand-típus; hiányzó kapcsolatot nem rekonstruálunk feltételezéssel.

**Részleges vagy ellentmondó lineage:** nem bizonyított eligible demand, ezért
nem fogyaszthat supplyt. A case forrása továbbra is értelmezhető lehet, de a
current evaluation nem bizonyítható; önmagában elegendő, bizonyított
source-invalid tény hiányában a resolver következménye UNDETERMINED.
Nem választjuk ki önkényesen, melyik ellentmondó adat az igaz.

Ugyanez vonatkozik a parent–child lifecycle ellentmondására. A státuszok puszta
eltérése nem jelent automatikusan ellentmondást; a Draft item fenti
kompatibilitási szabálya megmarad. Ez az ADR nem vezet be teljes
parent–child státuszszinkronizálási workflow-t.

### Átfedő kizárási és ellentmondási esetek

Az önmagában elegendő, bizonyított source-invalid tény elsőbbséget élvez:
source invalid, demand nem eligible, a következmény invalidation candidate.
Ilyen tény a Draft Customer Order, valamint a fent meghatározott terminális
vagy törölt forrás is. Incomplete/contradictory lineage vagy lifecycle miatt
nem végezhető authoritative evaluation esetén UNDETERMINED csak önmagában
elegendő, bizonyított source-invalid tény hiányában alkalmazandó.
Az ellentmondó adatokból nem találunk ki source-invalid tényt.

- Draft CO + partial production lineage → invalidation candidate.
- Cancelled CO + in-progress Production Order → invalidation candidate.
- Valid non-terminal CO + partial production lineage, önálló source-invalid
  tény nélkül → UNDETERMINED.
- Valid non-terminal CO + contradiction, önálló source-invalid tény nélkül
  → UNDETERMINED.

## Soft deletion és MaterialRequirement status

A current demandból kizárt a soft-deleted Customer Order, Customer Order Item,
MaterialRequirement és – ha production lineage létezik – Production Order.
Ezeknél a source invalid; a történeti Problem Case kapcsolat megmaradhat.
A `withTrashed()` történeti olvasási képesség, nem current demand engedély.

A `MaterialRequirement.status` nem Demand Eligibility input. A `calculated`,
`reserved`, `partially_available`, `missing`, `ordered`, `received`, `cancelled`
értékek jelenleg ellátottsági/procurement snapshot szemantikájúak. Még a
`cancelled` sem demand-invalidity bizonyíték, amíg külön domain-döntés nem ad
neki egyértelmű demand-cancellation jelentést. Forrás:
[MaterialRequirementStatus](../../app/Enums/MaterialRequirementStatus.php) és 0008.5.

## Resolver és RESOLVED boundary

Egy requirement demand eligibilityből való kiesése nem jelenti azt, hogy
`net_requirement = 0`. Hiányzó selected resultból nem készül nulla mennyiség.
Invalid Problem Case source nem jelent RESOLVED evaluationt.

RESOLVED csak valid source, végrehajtható authoritative evaluation és
`selected requirement.net_requirement = 0` mellett állapítható meg. Ugyanezen
bizonyíthatósági feltételek mellett pozitív net requirement esetén ACTIVE az
eredmény. Értelmezhető, de nem bizonyítható current állapotnál UNDETERMINED,
önmagában elegendő, bizonyított source-invalid tény hiányában.

Invalid source esetén a case invalidation candidate. Ez jelzés, nem új
evaluation érték és nem végrehajtott lifecycle transition. A policy nem zár le,
nem invalidál és nem nyit újra case-t. CLOSED és INVALIDATED case-ek nem
kezelhetők aktuális problémaként.

## Döntési mátrix

A sorok a megnevezett feltételt izolálják; az együttes source-invalid és
hiányos vagy ellentmondó lineage/lifecycle esetekre a fenti elsőbbségi szabály érvényes. A Yes sorokban
minden egyéb source feltétel teljesül, és current evaluation csak OPEN case-re
vonatkozik. A MaterialRequirement.status önmagában egyik sor döntését sem
változtatja meg.

| Scenario                                                     | Netting Demand Eligible | Case Source Valid        | Resolver consequence     |
| ------------------------------------------------------------ | ----------------------- | ------------------------ | ------------------------ |
| Valid non-terminal CO + item + PO, konzisztens lineage       | Yes                     | Yes                      | Evaluate                 |
| Draft CO                                                     | No                      | No                       | Invalidation candidate   |
| Completed CO                                                 | No                      | No                       | Invalidation candidate   |
| Cancelled CO                                                 | No                      | No                       | Invalidation candidate   |
| Completed item                                               | No                      | No                       | Invalidation candidate   |
| Cancelled item                                               | No                      | No                       | Invalidation candidate   |
| Completed PO                                                 | No                      | No                       | Invalidation candidate   |
| Cancelled PO                                                 | No                      | No                       | Invalidation candidate   |
| Completely absent production lineage, egyébként valid source | Yes, legacy-compatible  | Yes, legacy-compatible   | Evaluate with provenance |
| Partial production lineage                                   | No                      | Conditionally meaningful | UNDETERMINED\*           |
| Contradictory lineage                                        | No                      | Conditionally meaningful | UNDETERMINED\*           |
| Parent–child lifecycle contradiction                         | No                      | Conditionally meaningful | UNDETERMINED\*           |
| Soft-deleted source entity                                   | No                      | No                       | Invalidation candidate   |
| Bizonyított source-invalid tény és ellentmondás együtt       | No                      | No                       | Invalidation candidate   |

\* Önmagában elegendő, bizonyított source-invalid tény hiányában.

A Conditionally meaningful nem bizonyított valid source-ot és nem új
persistált source státuszt jelent; az értelmezhetőség és az aktuális állapot
bizonyíthatósága közötti különbséget jelöli.

## Következmények és későbbi implementációs irány

Egy közös, AI-független domain/application capability feleljen a demand
eligibility eldöntéséért. Koncepcionális neve lehet
`MaterialRequirementDemandEligibilityPolicy`; ez nem végleges osztálynév.
A normál full-scope netting és a Problem Case Resolver ugyanazt a szabályt
használja majd. Nem elfogadható, hogy Merlin eltérő demand scope-ból számoljon.

Az authoritative útvonal továbbra is a current requirement és rendelési/termelési
lineage vizsgálata, a teljes eligible competing scope nettingje, majd a konkrét
case requirement eredményének kiválasztása. Detection Snapshot, tárolt
`missing_quantity` és order-risk aggregátum nem current authority.

A későbbi változás módosítja a supplyért versengő demand halmazát, ezért a
kizárások más eligible requirementek coverage és net requirement eredményét is
befolyásolhatják. Ehhez külön implementációs és regressziós slice kell.

Ebben a dokumentációs slice-ban nincs policy vagy resolver implementáció,
quantity/supply változtatás, implicit unit conversion, permission-bővítés,
Merlin/AI hívás vagy lifecycle workflow. A Detection Snapshot immutable marad,
és resolver-futás önmagában nem hoz létre Evaluation History rekordot. A current
projection persistence részleteit ez az ADR nem bővíti.

## Mérlegelt, el nem fogadott irányok

- Minden nem soft-deleted requirement változatlanul versenyez: nem ellenőrzi
  a most rögzített demand lifecycle-feltételeket.
- Külön Merlin filter: eltérne a normál MRP authoritative scope-jától.
- Kötelező production lineage minden esetben: kizárná a dokumentált legacy
  kompatibilitást.
- MaterialRequirement.status mint demand lifecycle: összekeverné a snapshotot
  az authoritative üzleti igény érvényességével.
