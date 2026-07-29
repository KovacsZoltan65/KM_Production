import { describe, expect, it, vi } from "vitest";
import {
    notifyRequestError,
    resolveRequestError,
} from "@/Composables/useRequestError";

describe("useRequestError", () => {
    it.each([
        [403, "notifications.error.forbidden"],
        [419, "notifications.error.session_expired"],
        [422, "notifications.error.validation"],
        [500, "notifications.error.server"],
        [503, "notifications.error.server"],
    ])("a(z) %s státuszt biztonságos kulcsra képezi", (status, key) => {
        expect(resolveRequestError({ response: { status } }).summary).toBe(key);
    });

    it("válasz nélküli Axios hibát hálózati hibaként kezel", () => {
        expect(resolveRequestError({ request: {} }).summary).toBe(
            "notifications.error.network",
        );
    });

    it("nem jelenít meg nyers SQL- vagy stacküzenetet", () => {
        const resolved = resolveRequestError({
            response: {
                status: 500,
                data: {
                    message:
                        "SQLSTATE[23000] stack trace with internal details",
                },
            },
            message: "Sensitive exception",
        });

        expect(resolved.summary).toBe("notifications.error.server");
        expect(resolved.summary).not.toContain("SQLSTATE");
        expect(resolved.summary).not.toContain("Sensitive");
    });

    it("megszakított kéréshez nem ad toastot", () => {
        const toast = { add: vi.fn() };

        notifyRequestError(toast, { name: "CanceledError" });

        expect(toast.add).not.toHaveBeenCalled();
    });
});
