import { describe, expect, it } from "vitest";
import {
    canAccessNavigationItem,
    filterNavigationItems,
    findActiveNavigationHref,
    normalizeNavigationPath,
} from "@/Utils/navigation";

const items = [
    { labelKey: "navigation.inventory", href: "/admin/inventory" },
    { labelKey: "navigation.procurement", disabled: true },
    {
        labelKey: "navigation.procurement_dashboard",
        href: "/admin/procurement/dashboard",
    },
    { labelKey: "navigation.documents", disabled: true },
    {
        labelKey: "navigation.document_library",
        href: "/admin/documents",
    },
];

describe("navigation helper", () => {
    it("a route queryt, hash-t és záró perjelet figyelmen kívül hagyja", () => {
        expect(normalizeNavigationPath("/admin/inventory/?page=2#top")).toBe(
            "/admin/inventory",
        );
    });

    it("hiányzó URL-t gyökér útvonalként kezel", () => {
        expect(normalizeNavigationPath(null)).toBe("/");
    });

    it("csak a szükséges permission birtokában engedi a menüpontot", () => {
        expect(
            canAccessNavigationItem(items[0], ["inventory.view"]),
        ).toBe(true);
        expect(canAccessNavigationItem(items[0], [])).toBe(false);
    });

    it("super-admin szerepkörnél teljes navigációt enged", () => {
        expect(canAccessNavigationItem(items[0], [], ["super-admin"])).toBe(
            true,
        );
    });

    it("üres jogosultságlistánál minden védett menüpontot kiszűr", () => {
        expect(filterNavigationItems(items, [])).toEqual([]);
    });

    it("részleges jogosultságnál csak az érintett modult tartja meg", () => {
        expect(filterNavigationItems(items, ["inventory.view"])).toEqual([
            items[0],
        ]);
    });

    it("a szülő csoportot megtartja, ha van engedélyezett gyermeke", () => {
        expect(filterNavigationItems(items, ["documents.view"])).toEqual([
            items[3],
            items[4],
        ]);
    });

    it("a szülő csoportot elrejti, ha nincs engedélyezett gyermeke", () => {
        const visible = filterNavigationItems(items, ["inventory.view"]);
        expect(visible).not.toContain(items[1]);
        expect(visible).not.toContain(items[3]);
    });

    it("pontos útvonal-egyezést aktívnak talál", () => {
        expect(findActiveNavigationHref(items, "/admin/documents")).toBe(
            "/admin/documents",
        );
    });

    it("alroute és query esetén a megfelelő szülőt aktiválja", () => {
        expect(
            findActiveNavigationHref(
                items,
                "/admin/documents/12?version=2",
            ),
        ).toBe("/admin/documents");
    });

    it("hasonló prefixek közül a leghosszabb egyezést választja", () => {
        const overlapping = [
            { href: "/admin/inventory", labelKey: "navigation.inventory" },
            {
                href: "/admin/inventory/stock-reservations",
                labelKey: "navigation.reservations",
            },
        ];
        expect(
            findActiveNavigationHref(
                overlapping,
                "/admin/inventory/stock-reservations/4",
            ),
        ).toBe("/admin/inventory/stock-reservations");
    });

    it("más modul útvonalát nem aktiválja tévesen", () => {
        expect(findActiveNavigationHref(items, "/admin/intelligence")).toBe(
            undefined,
        );
    });
});
