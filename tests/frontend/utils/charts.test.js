import { describe, expect, it } from "vitest";
import {
    buildStatusChart,
    normalizeStatusChartValue,
    statusChartLabel,
} from "@/Utils/charts";
import { makeChartPoint } from "../fixtures/domain.js";

describe("status chart transzformáció", () => {
    it("többpontos adatsorból egyező hosszúságú szegmenseket készít", () => {
        const rows = [
            makeChartPoint({ label: "ready", value: 2 }),
            makeChartPoint({ label: "in_progress", value: 3 }),
        ];
        const chart = buildStatusChart(rows);

        expect(chart.total).toBe(5);
        expect(chart.segments).toHaveLength(rows.length);
        expect(chart.segments[0]).toMatchObject({
            label: "ready",
            value: 2,
            dasharray: "40 60",
            dashoffset: -0,
        });
        expect(chart.segments[1].dashoffset).toBe(-40);
    });

    it.each([[], null, undefined])("%s inputból üres diagramot készít", (rows) => {
        expect(buildStatusChart(rows)).toEqual({ total: 0, segments: [] });
    });

    it("egyetlen adatpont teljes körszegmenst kap", () => {
        expect(buildStatusChart([makeChartPoint({ value: 4 })]).segments[0])
            .toMatchObject({ dasharray: "100 0", dashoffset: -0 });
    });

    it("null, hiányzó, negatív és hibás számértéket nullára normalizál", () => {
        expect([null, undefined, -2, "hibás"].map(normalizeStatusChartValue))
            .toEqual([0, 0, 0, 0]);
    });

    it("stringként érkező számot számmá alakít", () => {
        expect(normalizeStatusChartValue("12.5")).toBe(12.5);
    });

    it("az input stabil sorrendjét megőrzi", () => {
        const chart = buildStatusChart([
            makeChartPoint({ label: "z", value: 1 }),
            makeChartPoint({ label: "a", value: 1 }),
        ]);
        expect(chart.segments.map(({ label }) => label)).toEqual(["z", "a"]);
    });

    it("azonos címkéjű pontokat külön szegmensként őriz meg", () => {
        const chart = buildStatusChart([
            makeChartPoint({ label: "ready", value: 1 }),
            makeChartPoint({ label: "ready", value: 2 }),
        ]);
        expect(chart.segments).toHaveLength(2);
        expect(chart.total).toBe(3);
    });

    it("hét szín után determinisztikusan újrakezdi a palettát", () => {
        const rows = Array.from({ length: 8 }, (_, index) =>
            makeChartPoint({ label: String(index), value: 1 }),
        );
        const segments = buildStatusChart(rows).segments;
        expect(segments[7].color).toBe(segments[0].color);
    });

    it("az underscore-os státuszcímkét olvashatóvá alakítja", () => {
        expect(statusChartLabel("waiting_for_qc")).toBe("waiting for qc");
        expect(statusChartLabel(null)).toBe("");
    });
});
