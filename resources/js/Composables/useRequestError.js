import { trans } from "laravel-vue-i18n";

const statusTranslationKeys = {
    401: "notifications.error.unauthenticated",
    403: "notifications.error.forbidden",
    404: "notifications.error.not_found",
    409: "notifications.error.conflict",
    419: "notifications.error.session_expired",
    422: "notifications.error.validation",
    429: "notifications.error.rate_limited",
    500: "notifications.error.server",
    503: "notifications.error.server",
};

/**
 * Biztonságos, lokalizált felhasználói üzenetté alakít egy kérési hibát.
 *
 * A szerver szabad szöveges hibaüzenetét szándékosan nem jeleníti meg.
 *
 * @param {unknown} error Az Axios- vagy Inertia-kérés hibája.
 * @param {string} [fallbackKey] Ismeretlen hiba fordítási kulcsa.
 * @returns {{aborted: boolean, status: number|null, summary: string, validation: boolean}}
 */
export function resolveRequestError(
    error,
    fallbackKey = "notifications.error.default",
) {
    const aborted =
        error?.name === "AbortError" ||
        error?.name === "CanceledError" ||
        error?.code === "ERR_CANCELED";
    const status = Number(error?.response?.status || error?.status) || null;
    const networkFailure =
        !aborted &&
        !status &&
        (error?.request || error?.code === "ERR_NETWORK");
    const translationKey = networkFailure
        ? "notifications.error.network"
        : statusTranslationKeys[status] || fallbackKey;

    return {
        aborted,
        status,
        summary: trans(translationKey),
        validation: status === 422,
    };
}

/**
 * Egy kérési hibát biztonságos PrimeVue toastként jelenít meg.
 *
 * @param {{add: (message: object) => void}} toast A PrimeVue toast szolgáltatás.
 * @param {unknown} error Az Axios- vagy Inertia-kérés hibája.
 * @param {{fallbackKey?: string, skipValidation?: boolean}} [options] Megjelenítési beállítások.
 * @returns {ReturnType<typeof resolveRequestError>} A normalizált hiba.
 */
export function notifyRequestError(toast, error, options = {}) {
    const resolved = resolveRequestError(error, options.fallbackKey);

    if (
        resolved.aborted ||
        (resolved.validation && options.skipValidation === true)
    ) {
        return resolved;
    }

    toast.add({
        severity: "error",
        summary: resolved.summary,
        life: 5000,
    });

    return resolved;
}
