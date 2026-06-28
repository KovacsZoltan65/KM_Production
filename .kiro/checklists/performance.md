# Purpose

Mandatory checklist for performance-sensitive changes.

# Checklist

- [ ] N+1 review completed.
- [ ] Indexes reviewed.
- [ ] Pagination used for large lists.
- [ ] Filtering is server-side where needed.
- [ ] Sorting is server-side where needed.
- [ ] Caching reviewed and safe.
- [ ] Queue usage reviewed for long-running tasks.
- [ ] Large uploads handled safely.
- [ ] AI queue used for AI/OCR processing.
- [ ] Lazy loading reviewed.
- [ ] Query count reviewed.
- [ ] Dashboard/report queries reviewed.
- [ ] Unnecessary eager loading removed.

# Common Mistakes

- Loading all table data into Vue.
- Adding broad search without indexes or limits.
- Running exports, reports, or AI processing during HTTP requests.
- Caching permission- or user-specific data unsafely.
- Adding dashboard metrics without query review.

# Completion Criteria

- Data loading is bounded and predictable.
- Long-running work is queued.
- Performance risks are measured, tested, or documented.
