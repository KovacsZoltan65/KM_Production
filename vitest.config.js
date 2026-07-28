import vue from "@vitejs/plugin-vue";
import { defineConfig } from "vitest/config";
import path from "node:path";

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            "@": path.resolve(import.meta.dirname, "resources/js"),
        },
    },
    test: {
        environment: "jsdom",
        environmentOptions: {
            jsdom: { url: "http://localhost/" },
        },
        setupFiles: ["tests/frontend/setup/setup.js"],
        include: ["tests/frontend/**/*.test.js"],
        // Each fork owns a jsdom heap; two workers cap peak memory while
        // preserving file-level parallelism on constrained local and CI hosts.
        pool: "forks",
        maxWorkers: 2,
        clearMocks: true,
        restoreMocks: true,
        coverage: {
            provider: "v8",
            reporter: ["text", "html", "json-summary"],
            reportsDirectory: "coverage/frontend",
            include: [
                "resources/js/Components/**/*.vue",
                "resources/js/Composables/**/*.js",
                "resources/js/Utils/**/*.js",
                "resources/js/Layouts/AdminLayout.vue",
                "resources/js/Pages/Admin/Inventory/StockReservations/Index.vue",
                "resources/js/Pages/Admin/Documents/**/*.vue",
            ],
            exclude: ["resources/js/app.js", "resources/js/bootstrap.js"],
        },
    },
});
