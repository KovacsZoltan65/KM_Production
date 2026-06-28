# Purpose

Define the workflow for OCR, AI, and computer vision features in KM_Production.

# When to Use

Use this workflow for AI document processing, OCR, barcode or QR reading, vision inspection, predictions, recommendations, and manufacturing copilot features.

# Required Context

- `AGENTS.md`
- `.kiro/steering/manufacturing-ai.md`
- `.kiro/knowledge/artificial-intelligence.md`
- `.kiro/decisions/0005-document-ai.md`
- `.kiro/playbooks/create-ai-feature.md`
- `.kiro/checklists/ai-feature.md`

# Workflow Steps

1. Define AI purpose.
   - Identify the workflow being assisted.
   - Confirm AI does not replace Laravel business rules.

2. Read required AI context.
   - Manufacturing AI steering.
   - Artificial intelligence knowledge.
   - Document AI ADR.

3. Design queue-based processing.
   - Long-running AI/OCR work must not block HTTP requests.

4. Isolate Python from Laravel business logic.
   - Python owns OCR, image processing, AI inference, and classification.
   - Laravel owns permissions, validation, workflows, database, and audit logs.

5. Define structured JSON output.
   - Include extracted values, confidence scores, evidence metadata, and errors.

6. Validate in Laravel.
   - Validate JSON schema.
   - Validate domain constraints.
   - Treat AI output as untrusted.

7. Add human review.
   - Required for low-confidence results.
   - Required for high-impact business changes.

8. Add audit logging.
   - Log execution, result, validation, review, approval, and failure.

9. Define retry strategy.
   - Avoid duplicate business writes.
   - Track processing status.

10. Handle failures.
    - Preserve business state.
    - Show safe messages.
    - Log technical details.

11. Test critical behavior.
    - Authorization, validation, low confidence, failure, retry, and approval.

# Required Quality Gates

- [ ] AI feature checklist completed.
- [ ] Queue used.
- [ ] Python has no direct database access.
- [ ] Structured JSON validated by Laravel.
- [ ] Confidence stored.
- [ ] Human review supported.
- [ ] Audit logging implemented.

# Documentation Updates

Update AI steering, knowledge, playbooks, prompts, or checklists only if the task creates a reusable AI pattern or changes the AI architecture.

# Final Report Format

- Summary
- Created files
- Modified files
- AI workflow
- Tests run
- Tests not run with reason
- Risks
- Next recommended step

# Common Failure Modes

- Saving AI output without Laravel validation.
- Running AI during the HTTP request lifecycle.
- Letting Python access the database.
- Missing confidence scores.
- Bypassing human review.
- Auto-approving quality or inventory changes.
