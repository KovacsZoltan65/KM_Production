const STATUS_COLORS = Object.freeze([
    "#2563eb",
    "#059669",
    "#d97706",
    "#dc2626",
    "#7c3aed",
    "#0f766e",
    "#64748b",
]);

export const normalizeStatusChartValue = (value) => {
    const number = Number(value);
    return Number.isFinite(number) && number > 0 ? number : 0;
};

export const buildStatusChart = (rows) => {
    const normalizedRows = Array.isArray(rows) ? rows : [];
    const total = normalizedRows.reduce(
        (sum, row) => sum + normalizeStatusChartValue(row?.value),
        0,
    );
    let offset = 0;

    const segments = normalizedRows.map((row = {}, index) => {
        const value = normalizeStatusChartValue(row.value);
        const length = total > 0 ? (value / total) * 100 : 0;
        const segment = {
            ...row,
            value,
            color: STATUS_COLORS[index % STATUS_COLORS.length],
            dasharray: `${length} ${100 - length}`,
            dashoffset: -offset,
        };
        offset += length;
        return segment;
    });

    return { total, segments };
};

export const statusChartLabel = (value) =>
    String(value || "").replaceAll("_", " ");
