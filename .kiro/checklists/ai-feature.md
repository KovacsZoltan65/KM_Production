# Purpose

Mandatory checklist for AI, OCR, and computer vision features.

# Checklist

- [ ] Queue used.
- [ ] No HTTP blocking.
- [ ] Python isolated.
- [ ] JSON validated.
- [ ] Confidence stored.
- [ ] Human review supported.
- [ ] Audit logging added.
- [ ] Retry strategy defined.
- [ ] Error handling implemented.
- [ ] Performance reviewed.
- [ ] Security reviewed.
- [ ] Monitoring reviewed.
- [ ] AI output treated as untrusted.
- [ ] Laravel owns business validation and database writes.
- [ ] Python has no direct database access.
- [ ] Low-confidence results require review.

# Common Mistakes

- Letting AI write directly to business tables.
- Running OCR synchronously in a request.
- Saving extraction output without validation.
- Missing confidence and review state.
- Auto-approving quality or inventory changes from AI output.

# Completion Criteria

- AI assists the workflow without replacing Laravel business rules.
- Processing is queued, auditable, reviewable, and secure.
