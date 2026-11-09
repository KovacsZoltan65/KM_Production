# RMA C3-02 — isolated transaction boundary PoC

**PREFLIGHT BLOCKED**, C3-02.4, 2026-11-06. The hardened filesystem preflight
stops on denied effective WRITE_DAC in the restricted Codex token. No MySQL,
SQL or PHP transaction test ran. The historical C3-02 result remains
**POC BLOCKED / NOT EXECUTED**.

See the [C3-02.4 report](../../../.kiro/designs/rma-0004-c3-02-4-launcher-hardening-results.md),
[ACL diagnosis](../../../.kiro/designs/rma-0004-c3-02-2-windows-acl-diagnostic-results.md),
[original report](../../../.kiro/designs/rma-0004-c3-02-transaction-boundary-poc-results.md)
and unchanged [historical manifest](evidence/results.json).
The database sources are an unexecuted prototype, not a verified executor.

## Files and isolation

- `launch.ps1`: Windows PowerShell **7.4+**, two explicit phases, default filesystem preflight.
- `Prototype.php`: standalone Laravel database-component executor without application bootstrap,
  routes, default connection, `.env` loading or existing service invocation.
- `schema.mysql`: synthetic InnoDB command/business/evidence tables and complete command routine.
- `run.php`: isolated setup/roles, T01–T14 assertions, sanitized evidence and owned-server shutdown.
- `worker.php`: independent runtime process without inherited PoC admin/definer secrets.
- `evidence/results.json`: historical ACL failure and BLOCKED case inventory; never regenerated
  by filesystem preflight.

The directory is outside normal Feature/Unit suites and application routing.
No dependency installation, existing configuration change or application migration is needed.

## Phase A: filesystem-only preflight

Default mode is `Preflight`. It creates only a fresh, cryptographically random
`RmaBoundaryPoc-<32 lowercase hex>` child of an approved OS temporary directory.
Only exact OS-derived LocalApplicationData/Temp or Windows/Temp roots are accepted;
arbitrary custom temporary roots fail closed. It requires local fixed NTFS with
persistent ACLs, checks every existing path component for reparse points, resolves
the canonical path, rejects UNC/repository/database paths and checks effective create access.
A native atomic create refuses collisions and never reuses a directory.

The launcher records the actual token, canonical parent/path, volume/file identity,
initial/final owner and DACL, effective access checks, ACL command diagnostics and cleanup.
It stops **before icacls** if effective WRITE_DAC is denied or cannot be verified.
No owner change, elevation, parent ACL change or restricted-token workaround is attempted.

Numeric grants are separate process arguments constructed with
`[string]::Concat('*', $Sid, ':(OI)(CI)F')`. The system-directory `icacls.exe` receives
`/inheritance:r /grant:r *SID:(OI)(CI)F`.
The [icacls documentation](https://learn.microsoft.com/en-us/windows-server/administration/windows-commands/icacls)
requires `*` for numeric SIDs; exit zero alone does not prove isolation.
The command captures stdout/stderr bytes as base64, OEM-decoded text, exit code immediately
and exception type/message/HResult, including start/timeout failures. If not invoked,
`attempted=false`, `executed=false` and `exitCode=null` are intentional.

### Owner and exact DACL policy

Policy is declared before inspecting the new directory:

- Owner must be the actual user SID; Administrators ownership is also permitted only
  when that SID is the token's default owner and Administrators is enabled, not deny-only.
- Ownership must remain unchanged. No take-ownership or automatic owner rewrite.
- DACL must be protected and canonical. Exactly one explicit user Allow FullControl
  ACE with ObjectInherit + ContainerInherit and no propagation flags is mandatory.
- Optional SYSTEM and Administrators ACEs are the only privileged exceptions; if present,
  they must have the same explicit Allow FullControl shape. No duplicate, deny, inherited,
  partial-rights or other-principal ACE is accepted.
- The actual process token must pass full-control `AccessCheck`, including restricting SIDs.
  [Restricted tokens](https://learn.microsoft.com/en-us/windows/win32/secauthz/restricted-tokens)
  must satisfy both ordinary and restricting SID checks.

The ordinary-user exclusion proof is limited to this protected allowlist and
unrelated tokens without user/SYSTEM/enabled-Administrators identities or overriding
privileges. SYSTEM/administrative recovery and Windows owner rights are explicit
privileged capabilities. Same-user processes, operator compromise, privileged recovery,
malicious concurrent parent replacement and alternate-account login tests are outside
the verified scope. No absolute security boundary or alternate-user runtime denial is claimed.
[AccessCheck](https://learn.microsoft.com/en-us/windows/win32/api/securitybaseapi/nf-securitybaseapi-accesscheck)
checks the current token against the captured descriptor, not every possible Windows account.

### Preflight invocation

Run in a reviewed local operator context; **do not elevate automatically**:

```powershell
pwsh -NoProfile -File ./tests/ProofOfConcept/RmaTransactionBoundary/launch.ps1 -Mode Preflight
$preflightExit = $LASTEXITCODE
```

Omitting `-Mode` also selects `Preflight`. No binary paths or credentials are required.
Retain the JSON stdout and immediate exit locally under the project's evidence policy;
it contains SID/path security metadata, so review it before sharing.
Exit 0 means `PREFLIGHT VERIFIED — LIMITED SCOPE`; 2 means `PREFLIGHT BLOCKED`;
3 means `PREFLIGHT FAILED` for an ACL invocation error/nonzero exit.
An unresolved cleanup or parent-integrity requirement takes precedence and returns 2.
Do not infer database authorization from any preflight result.

## Phase B: future transaction PoC

`-Mode PoC` additionally requires `-ConfirmAuthorizedPoC` and separate project-owner
authorization. It always runs Phase A first in the same invocation and never skips an
isolation gate. Successful default preflight never continues automatically into Phase B.
**Do not execute Phase B as part of C3-02.4.**

The historical `evidence/results.json` guard intentionally still blocks Phase B.
A separately reviewed evidence-preservation/run-output adaptation is required before a
future run; do not delete or overwrite the historical manifest to bypass the guard.
Both launcher and runner retain that protection. No runnable Phase B example is supplied here.

Future Phase B also requires explicit reviewed local binary paths, MySQL8.4, `--no-defaults`,
a new private run-owned data root, loopback-only unused nonstandard port, disabled X endpoint,
generated test-only credentials and a listener owned by the exact child PID before PHP.
Subsequent runner gates require matching data root/port/version and only system schemas
before setup; exact database allowlist is `rma_c302_disposable`.
No existing database, production credential, default application connection or `.env` is used.
There is no external-server mode.

Generated secrets remain in private bootstrap storage/process environment, never source,
password arguments or reports. The setup administrator controls only the fresh isolated server.
Runtime has protected-table SELECT and complete routine EXECUTE plus INSERT on a synthetic
caller-sentinel table; no protected DML/DDL/helper EXECUTE. Definer login is locked.
These grants and all Phase B lifecycle changes remain **unexecuted and unverified**.

## Proposed transaction contract and limits

Model C remains a dedicated executor-owned explicit Laravel/PDO root and fresh nonpersistent
connection. Registered active caller connections are rejected before the new connection factory.
The executor owns commit/rollback and bounded whole-command retry, at most two attempts1205/1213.
This is synthetic test policy, not accepted production retry policy.

The routine has no COMMIT/ROLLBACK/START TRANSACTION or session-variable authorization.
One INSERT into `poc_commands` triggers business and immutable evidence creation in the same
InnoDB statement. A trigger error should undo that statement while preserving unrelated prior
caller writes. This narrows C3-01's open totality mechanism to comply with C3-02's prohibition on
routine rollback of caller transactions; no procedure-owned Model B boundary is introduced.

The [MySQL trigger manual](https://dev.mysql.com/doc/refman/8.4/en/trigger-syntax.html) describes
the general statement rollback mechanism. This exact SQL/schema/grant contract is **unexecuted**.
A general multi-statement RMA finalizer cannot inherit this narrow guarantee.

Identity binds key/amount/actor/fault to a canonical hash. Duplicate identity should replay the
same locked result; changed payload should conflict. One synthetic actor is bound to the login
through SESSION_USER. This does not solve shared-login HTTP actor authentication or deployment
secret isolation. A connection registry is not global discovery of application transactions.

T08 would require two processes and actual server lock-wait evidence. T09 prepares a timeout1205
conflict, not necessarily a genuine1213 deadlock. T10 only simulates acknowledgement loss after
a confirmed PDO commit; real network/ambiguous commit remains absent. T14 simulates existing
service transaction shape on standalone Laravel connections without invoking existing services.
Even after a future successful run T10/T14 cannot be full proof without those missing cases.
All14 tests are currently BLOCKED; none of these planned assertions is a runtime result.

## Cleanup and verification limits

Cleanup checks current-invocation creation, exact canonical parent/path/random basename,
unchanged volume/file ID and owner, no reparse components and effective DELETE.
Phase A deletes only an empty directory, nonrecursively. On uncertainty it retains the path
and reports the reason. Future Phase B additionally requires own-server exit and scans all
descendants for reparse points before recursive deletion of the checked root.
Only the captured owned child may be stopped. No global service/process cleanup is allowed.

These repeated path/identity checks reduce mistakes; they are not an atomic handle-relative
recursive deletion proof against a malicious concurrent privileged/same-user actor.
A hostile writable parent requires a separately reviewed handle-based design.

C3-02.4 used targeted PowerShell parsing, inline in-memory/read-only negative checks,
filesystem preflight, Markdown/link/encoding/whitespace/security inspection and preservation
hashes. No full suite or database test ran. ACL01–ACL10 distinguish actual filesystem evidence,
unit-only results, blocked positive checks and missing alternate-principal/reparse experiments
in the [verification report](../../../.kiro/designs/rma-0004-c3-02-4-launcher-hardening-results.md).
