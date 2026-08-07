import { describe, expect, it } from "vitest";
import { route } from "@/Utils/routes";

describe("route", () => {
    it("felold paraméter nélküli útvonalat", () => {
        expect(route("admin.dashboard")).toBe("/admin/dashboard");
    });

    it("név szerinti és objektumparamétert URL-biztosan helyettesít", () => {
        expect(route("admin.users.update", { user: { id: "12/3" } })).toBe(
            "/admin/users/12%2F3",
        );
    });

    it("pozicionális primitív paramétert is kezel", () => {
        expect(route("admin.documents.show", 42)).toBe("/admin/documents/42");
    });

    it("feloldja a procurement source resource útvonalait", () => {
        expect(route("admin.item-suppliers.index")).toBe(
            "/admin/item-suppliers",
        );
        expect(route("admin.item-suppliers.update", 12)).toBe(
            "/admin/item-suppliers/12",
        );
    });

    it("feloldja a supply proposal resource és lifecycle útvonalait", () => {
        expect(route("admin.supply-proposals.index")).toBe(
            "/admin/supply-proposals",
        );
        expect(route("admin.supply-proposals.approve", 15)).toBe(
            "/admin/supply-proposals/15/approve",
        );
    });

    it("ismeretlen útvonalnál egyértelmű hibát ad", () => {
        expect(() => route("missing.route")).toThrow(
            "Unknown route name: missing.route",
        );
    });

    it("hiányzó kötelező paraméternél egyértelmű hibát ad", () => {
        expect(() => route("admin.items.update")).toThrow(
            "Missing route parameter [item]",
        );
    });
});
