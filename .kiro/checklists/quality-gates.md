# Rétegezett quality-gate checklist

## Fejlesztés közben

- [ ] `php tools/quality-gate.php affected --dry-run --explain` helyesen sorolja be a változást.
- [ ] Az érintett teszt vagy a fast gate lefutott.
- [ ] Modul lezárásakor a megfelelő `module` gate lefutott.
- [ ] Shared/core változásnál integration gate futott.
- [ ] Nem ismétlődött ugyanaz a teljes suite ugyanabban az ellenőrzési körben.

## Full gate indoka

Full gate szükséges, ha legalább egy igaz:

- a felhasználó kifejezetten kérte;
- merge, release vagy main-branch ellenőrzés történik;
- migration, dependency vagy tesztkonfiguráció változott;
- shared/core refaktor kockázata túlmutat az integration gate-en;
- célzott regresszió keresztmodul hibát jelez.

Tisztán PHP-only belső módosításnál ne fusson automatikusan teljes frontend,
coverage, build vagy Playwright. Egyszerű frontend modulmódosításnál ne fusson
teljes backend suite bizonyított keresztmodul ok nélkül.

## Lezárás

- [ ] A runner saját unit tesztjei zöldek.
- [ ] A process exit code és timeout behavior bizonyított.
- [ ] Nincs visszamaradt saját PHP, Node, Playwright vagy böngésző processz.
- [ ] A mátrix és a fejlesztői dokumentáció a tényleges fájlokat írja le.
- [ ] A futtatott és kihagyott kapuk tényszerűen dokumentáltak.
