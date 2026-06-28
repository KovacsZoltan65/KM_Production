# Purpose

Create an AI-assisted workflow that keeps Laravel in control of business rules and uses Python only for processing.

# Preconditions

- Review `.kiro/steering/manufacturing-ai.md`.
- Review `.kiro/decisions/0005-document-ai.md`.
- Confirm whether human review is required.
- Define structured JSON output schema.
- Define confidence thresholds.
- Confirm queue, timeout, logging, and retry expectations.

# Implementation Steps

1. User upload or request.
   - Validate input.
   - Authorize the action.
   - Store files safely when uploads are involved.

2. Queue the AI task.
   - Do not block the HTTP request.
   - Store processing status.
   - Include correlation identifiers.

3. Python worker processes the task.
   - Perform OCR, image processing, AI inference, or classification.
   - Do not access the Laravel database.
   - Do not execute uploaded files.

4. Return structured JSON.
   - Include extracted fields.
   - Include confidence scores.
   - Include processing metadata and errors where applicable.

5. Laravel validates the AI output.
   - Validate schema.
   - Validate domain constraints.
   - Validate permissions and workflow state.
   - Treat AI output as untrusted input.

6. Human review.
   - Required for low-confidence results.
   - Required for high-impact business changes.
   - Show source evidence where practical.

7. Approval.
   - Save only approved and validated data.
   - Use services for business workflow changes.
   - Use transactions where required.

8. Database update.
   - Laravel owns all database writes.
   - Python never writes business data directly.

9. Audit log.
   - Log AI execution.
   - Log validation result.
   - Log review decision.
   - Log final accepted changes.

10. Notification.
    - Notify users when processing completes, fails, or requires review.

Workflow:

```txt
User Upload
↓
Queue
↓
Python Worker
↓
OCR / AI
↓
Structured JSON
↓
Laravel Validation
↓
Human Review
↓
Approval
↓
Database
↓
Audit Log
↓
Notification
```

# Validation Checklist

- [ ] AI task is queued.
- [ ] Python has no direct database access.
- [ ] AI output is structured JSON.
- [ ] Confidence is included.
- [ ] Laravel validates output before saving.
- [ ] Low-confidence results require review.
- [ ] Errors are logged safely.
- [ ] Retries cannot create duplicate business changes.

# Testing Checklist

- [ ] Valid AI result is accepted after validation.
- [ ] Invalid JSON schema is rejected.
- [ ] Low-confidence result requires review.
- [ ] Unauthorized user cannot approve result.
- [ ] Failed processing updates status safely.
- [ ] Retry behavior is idempotent where needed.
- [ ] Audit logs are created.

# Common Mistakes

- Running long AI work inside HTTP requests.
- Letting Python write directly to the database.
- Saving AI output without Laravel validation.
- Missing confidence scores.
- Auto-approving low-confidence or failed results.
- Logging sensitive raw documents.
- Tying business logic to one AI provider.

# Completion Criteria

- AI assists the workflow without owning business rules.
- Laravel validates, reviews, saves, and audits all business data.
- Processing is queued, observable, and recoverable.
