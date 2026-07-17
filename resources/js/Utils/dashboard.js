export const formatDashboardNumber = (value, digits = 3) => {
    if (value === null || value === undefined || value === "") {
        return "-";
    }

    const number = Number(value);
    return Number.isFinite(number) ? number.toFixed(digits) : "-";
};

export const loadStatusSeverity = (status) =>
    ({ green: "success", yellow: "warn", red: "danger" })[status] ||
    "secondary";

export const capacityTone = (value) => {
    const number = Number(value || 0);
    if (number >= 90) return "border-red-200 bg-red-50 text-red-700";
    if (number >= 70) return "border-amber-200 bg-amber-50 text-amber-700";
    return "border-emerald-200 bg-emerald-50 text-emerald-700";
};
