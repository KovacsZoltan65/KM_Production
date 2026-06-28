# Purpose

Mandatory checklist for security-sensitive changes.

# Checklist

- [ ] Authorization enforced on backend.
- [ ] Policies updated.
- [ ] Permissions created or reviewed.
- [ ] Validation uses FormRequests where applicable.
- [ ] Transactions protect critical workflows.
- [ ] Secrets are not committed.
- [ ] Secrets are not logged.
- [ ] Logging avoids sensitive data.
- [ ] Upload validation covers MIME type and size.
- [ ] Uploaded files are not trusted by extension.
- [ ] AI security boundaries preserved.
- [ ] AI/Python does not write directly to database.
- [ ] Audit logging added for important business actions.
- [ ] No sensitive logging.
- [ ] No frontend-only security checks.

# Common Mistakes

- Hiding buttons in Vue instead of enforcing policies.
- Logging tokens, files, or sensitive payloads.
- Trusting AI/OCR output without Laravel validation.
- Allowing file previews without authorization.
- Hard-deleting traceability records casually.

# Completion Criteria

- Protected reads and writes are authorized.
- Inputs, uploads, and AI outputs are validated.
- Security-sensitive behavior is tested or explicitly reviewed.
