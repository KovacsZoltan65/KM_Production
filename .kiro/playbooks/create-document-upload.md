# Purpose

Create a secure document upload workflow for attaching evidence to supported business entities.

# Preconditions

- Confirm the supported target entity.
- Confirm allowed document types.
- Confirm permissions for upload, view, update, and delete.
- Confirm file storage location and visibility.
- Review security and document steering guidance.

# Implementation Steps

1. Define upload validation.
   - Validate MIME type.
   - Validate size.
   - Do not trust file extension.
   - Validate document type and target entity.

2. Store the file safely.
   - Generate a safe server-side filename.
   - Store outside public paths unless intentionally public.
   - Keep original filename only as metadata.

3. Create the database record.
   - Store document type, path, MIME type, size, uploader, and target entity.
   - Store relevant metadata such as supplier, revision, or certificate number where applicable.

4. Attach the document.
   - Link it to supported entities only.
   - Supported examples: items, operation sequences, production orders, operations, quality inspections.

5. Add preview or download behavior.
   - Authorize access.
   - Restrict previews to safe MIME types.
   - Avoid exposing storage internals.

6. Add security protections.
   - Never execute uploaded files.
   - Scan or sanitize files where possible.
   - Treat extracted content as untrusted.

7. Add audit logging.
   - Log upload, replacement, deletion, classification, and review actions.
   - Include actor, target entity, document type, and metadata.

8. Prepare for future OCR processing.
   - Store OCR status where useful.
   - Queue OCR or AI tasks instead of processing in the request lifecycle.
   - Require Laravel validation and review before extracted data affects business records.

# Validation Checklist

- [ ] MIME type is validated.
- [ ] Size is validated.
- [ ] Extension is not trusted.
- [ ] File is stored safely.
- [ ] Metadata is recorded.
- [ ] Document is attached to an allowed entity.
- [ ] Preview/download is authorized.
- [ ] Upload action is audited.

# Testing Checklist

- [ ] Valid upload succeeds.
- [ ] Invalid MIME type fails.
- [ ] Oversized file fails.
- [ ] Unauthorized upload is forbidden.
- [ ] Unauthorized preview/download is forbidden.
- [ ] Metadata is persisted.
- [ ] Audit log is created.

# Common Mistakes

- Trusting file extensions.
- Storing sensitive files publicly by default.
- Forgetting authorization on preview/download routes.
- Losing the link between document and business entity.
- Running OCR synchronously during upload.
- Treating OCR output as trusted.

# Completion Criteria

- Uploads are secure, authorized, and auditable.
- Documents remain linked to business evidence.
- Future OCR/AI processing can be added without bypassing Laravel validation.
