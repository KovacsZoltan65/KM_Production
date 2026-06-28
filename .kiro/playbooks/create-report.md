# Purpose

Create a report for filtered, grouped, exportable business data in KM_Production.

# Preconditions

- Define report purpose and audience.
- Define required permissions.
- Identify source data and business rules.
- Confirm filters, sorting, grouping, totals, and export formats.
- Confirm whether report generation should be queued.

# Implementation Steps

1. Define report scope.
   - Identify included entities and fields.
   - Define date range behavior.
   - Define grouping and totals.

2. Add authorization.
   - Protect report view and export.
   - Restrict sensitive data.

3. Implement filtering.
   - Use explicit filters.
   - Validate filter values.
   - Keep filters consistent with API and admin page conventions.

4. Implement sorting.
   - Whitelist sortable fields.
   - Support predictable ascending and descending behavior.

5. Implement grouping.
   - Define grouping levels clearly.
   - Examples: item, supplier, production order, work center, quality result, date.

6. Implement totals.
   - Define sum, count, average, or rate calculations.
   - Keep calculations in services or repositories, not Vue.

7. Implement export.
   - Support required formats such as CSV, Excel, or PDF.
   - Queue large exports.
   - Preserve filters and user context in exported output.

8. Add audit logging.
   - Log report exports when data is sensitive or business-critical.
   - Include actor, report type, filters, and timestamp.

9. Add performance controls.
   - Use efficient queries.
   - Paginate interactive views.
   - Queue long-running reports.
   - Avoid loading unnecessary relationships.

10. Add localization.
    - Translate headings, labels, statuses, and enum values.

# Validation Checklist

- [ ] Report has a clear business purpose.
- [ ] View and export are authorized.
- [ ] Filters are validated.
- [ ] Sorting is whitelisted.
- [ ] Grouping is clear.
- [ ] Totals are correct.
- [ ] Export formats work.
- [ ] Sensitive exports are audited.
- [ ] Large reports do not block requests.

# Testing Checklist

- [ ] Authorization is enforced.
- [ ] Filters return expected data.
- [ ] Sorting is correct.
- [ ] Grouping is correct.
- [ ] Totals are correct.
- [ ] CSV export works if supported.
- [ ] Excel export works if supported.
- [ ] PDF export works if supported.
- [ ] Large export behavior is safe.

# Common Mistakes

- Building reports from unbounded datasets in the request lifecycle.
- Exposing sensitive data without permission checks.
- Calculating totals inconsistently between screen and export.
- Ignoring timezone or date range boundaries.
- Hardcoding column labels.
- Forgetting audit logs for sensitive exports.

# Completion Criteria

- Report output is accurate, authorized, localized, exportable, and performant.
- Filters, sorting, grouping, and totals are tested for critical cases.
