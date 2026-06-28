# Purpose

Create a dashboard that presents operational metrics, charts, tables, and alerts for KM_Production users.

# Preconditions

- Define the dashboard audience.
- Define required permissions.
- Identify metrics and source data.
- Confirm filters and time ranges.
- Confirm performance expectations.
- Review localization and frontend steering guidance.

# Implementation Steps

1. Define metrics.
   - Use business names.
   - Define calculation rules.
   - Identify required filters.

2. Create service and repository methods.
   - Keep metric orchestration in services.
   - Keep aggregation queries in repositories.
   - Avoid business calculations in Vue.

3. Add permissions.
   - Protect dashboard access.
   - Protect sensitive metrics.

4. Add filters.
   - Examples: date range, factory unit, work center, status, supplier, item type.
   - Keep filter state in query parameters where useful.

5. Add cards.
   - Show important totals and status indicators.
   - Keep labels localized.

6. Add charts.
   - Use clear visualizations.
   - Avoid misleading scales.
   - Provide empty states.

7. Add tables.
   - Use server-side pagination for large data.
   - Use sorting and filtering where appropriate.

8. Add performance protections.
   - Use efficient queries.
   - Cache stable or expensive metrics where appropriate.
   - Avoid loading large relationships unnecessarily.

9. Build responsive design.
   - Ensure desktop and mobile layouts remain usable.
   - Avoid clipping, overlap, and unstable layout shifts.

10. Add localization.
    - Use shared translation keys.
    - Resolve labels at runtime.

# Validation Checklist

- [ ] Metrics are defined and business-approved.
- [ ] Access is permission-protected.
- [ ] Filters work predictably.
- [ ] Cards, charts, and tables have loading and empty states.
- [ ] Expensive metrics are optimized or cached.
- [ ] Labels are localized.
- [ ] Layout is responsive.

# Testing Checklist

- [ ] Authorized users can view dashboard.
- [ ] Unauthorized users are denied.
- [ ] Metric calculations are correct.
- [ ] Filters affect metrics correctly.
- [ ] Empty data states are handled.
- [ ] Performance-sensitive queries are covered where practical.

# Common Mistakes

- Calculating business metrics only in Vue.
- Exposing sensitive metrics without permissions.
- Loading too much data into one page.
- Hardcoding labels.
- Ignoring empty states.
- Adding charts without clear business meaning.

# Completion Criteria

- Dashboard metrics are accurate, permission-aware, localized, and performant.
- Users can filter and interpret operational data clearly.
