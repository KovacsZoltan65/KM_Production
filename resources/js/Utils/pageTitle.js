export const DEFAULT_APP_NAME = "KM Production";

/**
 * Formats the title emitted by Inertia's Head component.
 *
 * @param {string|null|undefined} title
 * @param {string} appName
 * @returns {string}
 */
export const formatPageTitle = (title, appName = DEFAULT_APP_NAME) => {
    const normalizedTitle = typeof title === "string" ? title.trim() : "";

    if (!normalizedTitle || normalizedTitle === appName) {
        return appName;
    }

    if (normalizedTitle.endsWith(` | ${appName}`)) {
        return normalizedTitle;
    }

    return `${normalizedTitle} | ${appName}`;
};
