# Learning Center vízió

## Cél

Ez a dokumentum a Learning Center hosszú távú termékvízióját rögzíti. Nem technikai specifikáció, hanem iránytű: miért épül a modul, milyen problémát old meg, és milyen szerepet tölthet be a KM_Production rendszerben több fejlesztési cikluson át.

## Tervezett tartalom

- A Learning Center létjogosultsága.
- A felhasználói és szervezeti probléma.
- Hosszú távú célkép.
- Alapelvek, amelyek az implementációt vezetik.
- Kapcsolat a Knowledge Unit, Knowledge Graph és Context Engine szemlélettel.

## Miért épül?

A KM_Production nem egyszerű CRUD rendszer, hanem gyártási működést támogató, összetett üzleti platform. A felhasználóknak nemcsak azt kell tudniuk, hova kattintsanak, hanem azt is, hogy egy művelet milyen készlet-, minőségügyi, traceability, kapacitás- vagy jogosultsági következménnyel jár.

A Learning Center azért épül, hogy ez a tudás ne különálló kézikönyvekben, informális betanításban vagy support beszélgetésekben éljen, hanem a rendszer természetes részévé váljon.

## Milyen problémát old meg?

A gyártási rendszerekben a tudás gyakran szétszóródik:

- tapasztalt felhasználók fejében
- elavuló dokumentumokban
- issue-kommentekben
- email szálakban
- egyszeri support válaszokban
- képernyőn kívüli belső szabályzatokban

Ez lassítja az onboardingot, növeli a hibák esélyét, és túl nagy terhet rak a kulcsemberekre. A Learning Center célja, hogy a kritikus operatív tudást a megfelelő pillanatban, a megfelelő részletességgel tegye elérhetővé.

## Hosszú távú cél

A hosszú távú cél egy olyan beépített tudásréteg, amely:

- támogatja az új felhasználók betanulását
- segíti a tapasztalt felhasználókat ritka vagy összetett helyzetekben
- csökkenti a support és mentorálási terhelést
- konzisztens szakmai magyarázatokat ad
- összekapcsolja a dokumentációt a rendszer valós állapotával
- előkészíti a biztonságos, ellenőrzött AI asszisztenciát
- megőrzi a szervezeti tudást akkor is, ha folyamatok vagy szerepkörök változnak

## Alapvízió

A Learning Center ne külön ablak legyen, ahová a felhasználó csak akkor megy, ha már elakadt. Legyen olyan réteg, amely csendesen jelen van a munkafolyamatban:

- kezdő felhasználónak vezet
- haladó felhasználónak jelez
- profi felhasználónak csak kérésre segít
- hiba esetén magyaráz
- tanuláskor útvonalat ajánl
- bizonytalanság esetén megmutatja, miért történik valami

## Szakmai alapelvek

- A segítség nem kerülheti meg az üzleti szabályokat.
- A dokumentáció nem lehet elszakítva az aktuális kontextustól.
- A tudás legyen újrafelhasználható, review-zható és verziózható.
- A felhasználó maradjon kontrollban.
- Az automatikus ajánlás legyen magyarázható.
- Az AI csak ellenőrzött tudásra és jogosult kontextusra épülhet.
- A cél nem a felhasználó minősítése, hanem a hatékonyabb tanulás és biztonságosabb működés.

## Jövőkép

Érett állapotában a Learning Center a KM_Production tudásinfrastruktúrája lehet. Nemcsak dokumentációt jelenít meg, hanem összeköti a tudást, a felhasználói kontextust, a tanulási útvonalakat, a hibák magyarázatát és a későbbi AI támogatást.

Egy új raktáros megtanulhatja a készletmozgásokat anélkül, hogy a teljes kézikönyvet végigolvasná. Egy termelésvezető a kapacitástervezési nézetben kaphat magyarázatot a késési kockázatokra. Egy adminisztrátor jogosultsági hiba esetén nemcsak tiltást lát, hanem megérti, melyik szerepkör vagy jogosultság hiányzik.

Ez a cél: a rendszer ne csak végrehajtson, hanem tanítson is, mégpedig a munka ritmusához illeszkedve.

## Kapcsolódó témák

- [Knowledge Unit](knowledge-unit.md)
- [Knowledge Graph](knowledge-graph.md)
- [Context Engine](context-engine.md)
- [Learning Engine](learning-engine.md)
- [Jövőbeni ötletek](future-ideas.md)
