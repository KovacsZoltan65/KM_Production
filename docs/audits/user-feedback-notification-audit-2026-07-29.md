# User-feedback notification audit — 2026-07-29

## Scope

The audit covered the Laravel, Inertia, Vue, PrimeVue, localization and test
layers of every registered web write route and its frontend trigger.

- 152 registered web routes
- 84 non-GET routes
- 75 admin write routes
- 8 authentication, profile and preference write routes
- 1 framework storage upload route, which is not called by the application UI
- 27 frontend files containing Inertia form/router mutation triggers
- the document download route and its streamed error behavior

## Initial findings

The backend already returned localized, domain-specific success flashes from
73 admin mutation actions, but `HandleInertiaRequests` did not share any of
them. Consequently those 73 successful operations had no reliable visible
notification contract.

Seventeen admin Index/Show implementations and the shared CRUD component
duplicated success-only flash watchers and local `<Toast />` instances. They
did not support error, warning or informational flashes and could double-show
messages once a central prop was introduced.

There was no common safe mapping for 401, 403, 404, 409, 419, 422, 429, 500,
503, network and unknown client request failures. Common CRUD deletion had no
pending/error callbacks. Common CRUD validation kept field errors but offered
no consistent generic feedback or first-invalid-field focus.

Profile update, password update and locale change returned no success
notification. Two document-link validation failures contained hardcoded
English messages. No Vue code directly rendered an Axios `error.message` or
`response.data.message`, but there was no reusable guard preventing that
anti-pattern.

## Notification architecture before

```text
controller redirect + success session flash
    -> flash was not shared by Inertia
    -> page-specific success-only watcher attempted to read missing prop
```

Validation errors used normal Laravel/Inertia field errors. HTTP exceptions
used the framework response. Axios code generation had its own local success
and safe fallback error toast.

## Notification architecture after

```text
controller/service
    -> redirect + success/error/warning/info flash
    -> HandleInertiaRequests allow-listed flash prop
    -> AdminLayout / FlashToast
    -> PrimeVue ToastService
```

Client requests use `resolveRequestError` / `notifyRequestError`. The helper
maps known status and network failures to shared translation keys and never
trusts arbitrary backend exception text.

HTTP 403, 404, 419, 429, 500 and 503 responses render the safe Inertia
`Error` page with the original status code. JSON/API error behavior is left
unchanged.

## Backend flash contract

Only these nullable string keys are exposed:

```text
flash.success
flash.error
flash.warning
flash.info
```

Unrelated session data is not included. The contract is verified by a feature
test.

## Frontend toast contract

| Flash key | PrimeVue severity | Lifetime |
| --------- | ----------------- | -------: |
| `error`   | `error`           |  5000 ms |
| `warning` | `warn`            |  4000 ms |
| `success` | `success`         |  2500 ms |
| `info`    | `info`            |  3500 ms |

Simultaneous messages are displayed in error, warning, success, info order.
An identical reactive prop value is displayed once; clearing the flash resets
the signature so a later legitimate identical operation can notify again.
Initial mount and later Inertia prop updates are both supported.

The global Inertia validation event adds one generic error toast while the
field messages remain in their forms. The network-error event adds a safe
network toast and ignores aborted requests.

## Implemented fixes

- Added central flash sharing and global authenticated-layout toast handling.
- Removed all 17 duplicated page-level success watchers and obsolete page
  Toast instances.
- Added safe request-error normalization and tests for status, network,
  cancellation and raw-internal-detail protection.
- Added safe localized Inertia error pages for authorization, missing page,
  expired session, throttling and server availability failures.
- Kept common CRUD dialogs open on error, preserved entered data and field
  errors, focused the first invalid field, and added deletion pending cleanup.
- Preserved code generation, generated-value collision handling,
  `_code_was_generated`, `generatedValues` and immutable edit fields.
- Added pending/double-submit guards to common CRUD deletion, production task
  start/finish, document approval/version/delete, customer-order
  confirm/cancel, production-plan approve/generate, purchase-order
  approve/close, purchase-requisition approval, goods-receipt posting,
  capacity scheduling/simulation and existing stock-reservation release.
- Added localized profile, password and locale success flashes.
- Localized controlled document-link validation errors.
- Preserved direct streamed downloads: successful downloads show no
  misleading state-change toast; authorization/missing/server failures render
  a safe visible error response.

## Error behavior

| Failure          | User-visible behavior                                                                                 |
| ---------------- | ----------------------------------------------------------------------------------------------------- |
| validation / 422 | one generic error toast plus unchanged field errors                                                   |
| 401              | authentication middleware redirects to localized login UI                                             |
| 403              | localized access-denied Error page                                                                    |
| 404              | localized not-found Error page                                                                        |
| 409              | localized conflict message for client requests; domain conflicts remain controlled Laravel validation |
| 419              | localized expired-session Error page/message                                                          |
| 429              | localized rate-limit Error page/message                                                               |
| 500 / 503        | safe generic localized Error page/message                                                             |
| network          | localized network toast                                                                               |
| aborted          | intentionally no toast                                                                                |
| unknown          | safe localized generic error                                                                          |

No raw exception, SQL or stack text is selected for display by the new
contracts.

## Localization changes

Hungarian and English keys were added for notification error categories,
no-change warning, safe HTTP error pages, page reload, profile/password/locale
success and controlled document-link validation. Both JSON catalogs contain
727 synchronized keys (29 new keys in each language, from 698).

## Automated evidence

- `php artisan test tests/Feature/UserFeedbackNotificationTest.php`:
  7 tests, 35 assertions, passed.
- `php artisan test`: 378 tests, 1066 assertions, passed.
- `npm run test:frontend`: 22 files, 189 tests, passed.
- Focused notification/frontend regression: 3 files, 36 tests, passed.
- `npm run test:frontend:coverage`: 189 tests passed; 80.80% statements,
  81.27% branches, 65.37% functions and 80.69% lines.
- `npm run i18n:check`: 727 synchronized keys, passed.
- `npm run build`: 957 modules transformed, production build passed.
- `vendor/bin/pint --test`: passed.
- `composer analyse`: PHPStan passed with no errors.
- `composer install --no-interaction --prefer-dist`: lock verification,
  autoload generation and package discovery passed; no package change was
  required.
- `npm ci`: attempted twice, but Windows rejected unlinking the in-use native
  `lightningcss` binary with `EPERM`. A non-destructive `npm install`
  successfully restored/verified dependencies; the build and 36 focused
  notification tests passed afterward. NPM reported 9 existing high-severity
  dependency advisories.
- `git diff --check`: passed.

## Playwright coverage

`tests/e2e/workflows/user-feedback.spec.js` covers common CRUD create,
validation, update and delete, including modal retention, field error, visible
error toast, visible success toasts and row state. Existing customer-order and
stock-reservation flows now also assert their visible domain success toasts.

The new user-feedback scenario passed in an isolated Chromium run. All five
selected scenarios passed at least once in isolated/clean runs. A final
five-scenario batch remained unstable on the local PHP development server:
two scenarios passed and three failed from request/asset timing or retained
fixture/localization state. This batch result is not reported as green.

## Manual verification matrix

The in-app browser control could not initialize because its execution
environment rejected the session metadata with
`codex/sandbox-state-meta: missing field sandboxPolicy`. It failed before a
browser page could be opened, so no manual browser result is claimed.
Automated Playwright evidence is intentionally kept separate.

## Known risks

- The framework `storage/{path}` PUT route is registered by Laravel but is not
  triggered by KM_Production frontend code; it remains outside the
  application notification contract.
- Authentication pages intentionally retain their persistent field/status
  messages instead of adding redundant guest-layout toasts.
- The selected E2E batch is locally flaky when scenarios are run together
  against the single-process PHP development server. The individual feedback
  path is green, but the combined batch should be stabilized or rerun in the
  normal CI web-server environment before release.
- Manual browser verification remains outstanding until the in-app browser
  environment can initialize successfully.
- A clean `npm ci` remains outstanding because unknown Node processes lock
  native build binaries on this Windows host. They were not terminated without
  being able to identify their ownership. `npm install` and post-install
  verification succeeded, but this is not equivalent evidence to a clean CI
  install.
- `npm install` reports 9 high-severity dependency advisories. No automatic
  audit fix was applied because dependency upgrades are outside this task and
  could introduce breaking changes.
