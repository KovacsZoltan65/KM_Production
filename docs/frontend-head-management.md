# Frontend head- és lapcímkezelés

## Döntés

Az alkalmazás egyetlen head-kezelője az Inertia Vue adaptere. Az Inertia page komponensek az `@inertiajs/vue3` csomagból importált `<Head>` komponenssel adják meg a lokalizált lapcímet, a `resources/js/app.js` központi callbackje pedig egyszer fűzi hozzá az alkalmazás nevét:

```text
{lokalizált lapcím} | KM Production
```

Üres lapcímnél a fallback az alkalmazás neve. Az alap HTML head továbbra is a `resources/views/app.blade.php` fájlban él: itt marad a charset, viewport, Vite assetek, az Inertia fallback `<title>` és az `@inertiaHead` kimenet.

Az `@vueuse/head` plugin nem része az architektúrának. Korábban kizárólag a bootstrapban volt regisztrálva, miközben sem `useHead`, sem `useSeoMeta`, sem más Unhead API nem volt használatban. A csomag eltávolítása megszüntette az `unhead@1.11.20` sérülékeny tranzitív láncát anélkül, hogy a head viselkedése változott volna.

## Új vagy módosított oldal

- Importáld a `Head` komponenst az `@inertiajs/vue3` csomagból.
- Adj át Laravel JSON fordítási kulcsból származó címet: `<Head :title="$t('domain.page.title')" />`.
- Ne írd bele kézzel a `KM Production` suffixet; ezt a központi formatter adja hozzá.
- Ne írj közvetlenül a `document.title` értékébe alkalmazáskódból.
- Meta- vagy linktag igény esetén is először az Inertia `<Head>` slot API-ját használd. Második head-manager bevezetéséhez külön architekturális döntés és SSR/SEO indoklás szükséges.

## Quality gate

Helyi ellenőrzés:

```bash
npm ci
npm audit
npm audit --omit=dev
npm run test:frontend
npm run build
npm run test:e2e
```

A Vitest head-regresszió ellenőrzi a fallbacket, a suffixet és a DOM-ban látható címváltást. A Playwright navigációs teszt valódi Inertia előre- és visszanavigáció közben ellenőrzi a böngésző lapcímét. A frontend GitHub Actions job mindkét auditot blokkoló lépésként futtatja.
