export const makeUser = (overrides = {}) => ({
    id: 1,
    name: "Teszt Elek",
    email: "teszt@example.test",
    ...overrides,
});

export const makePermissions = (...permissions) => permissions;

export const makeRecord = (overrides = {}) => ({
    id: 1,
    name: "Teszt rekord",
    active: true,
    ...overrides,
});

export const makePagination = (data = [], overrides = {}) => ({
    data,
    current_page: 1,
    per_page: 10,
    total: data.length,
    last_page: 1,
    ...overrides,
});

export const makeSelectOption = (overrides = {}) => ({
    id: 1,
    label: "Választható érték",
    ...overrides,
});

export const makeValidationErrors = (overrides = {}) => ({
    name: "A név megadása kötelező.",
    ...overrides,
});

export const makeAuthPageProps = (overrides = {}) => ({
    auth: {
        user: makeUser(),
        permissions: [],
        roles: [],
        ...(overrides.auth || {}),
    },
    flash: {},
    preferences: { locale: "hu", availableLocales: [] },
    ...overrides,
});

export const makeStockReservation = (overrides = {}) => ({
    id: 1,
    reserved_quantity: "2.500",
    status: "active",
    reserved_at: "2026-07-16T08:30:00Z",
    released_at: null,
    item: { item_number: "ITEM-001", name: "Alumínium lemez" },
    location: { code: "A-01" },
    item_batch: null,
    customer_order_item: null,
    production_order: null,
    reserver: { name: "Raktáros" },
    ...overrides,
});

export const makeDocument = (overrides = {}) => ({
    id: 1,
    title: "Munkautasítás",
    description: "Gyártási leírás",
    original_filename: "utasitas.pdf",
    document_type: "work_instruction",
    documentable_type: "production_order",
    documentable_id: 12,
    version: 2,
    file_size: 2048,
    mime_type: "application/pdf",
    checksum: "abc123",
    approved: false,
    is_current: true,
    created_at: "2026-07-16T08:30:00Z",
    approved_at: null,
    uploader: { name: "Feltöltő" },
    approver: null,
    ...overrides,
});

export const makeDocumentVersion = (overrides = {}) => ({
    id: 2,
    version: 1,
    original_filename: "utasitas-v1.pdf",
    is_current: false,
    approved: true,
    created_at: "2026-07-15T08:30:00Z",
    uploader: { name: "Feltöltő" },
    ...overrides,
});

export const makeDashboardMetric = (overrides = {}) => ({
    label: "Kihasználtság",
    value: 72,
    detail: "Aktuális műszak",
    ...overrides,
});

export const makeFactoryLoad = (overrides = {}) => ({
    factory_unit: "Megmunkálás",
    available_minutes: 480,
    reserved_minutes: 360,
    utilization: 75,
    current_queue: 3,
    status: "yellow",
    ...overrides,
});

export const makeChartPoint = (overrides = {}) => ({
    label: "in_progress",
    value: 4,
    ...overrides,
});
