import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import { spawnSync } from "node:child_process";
import { createRequire } from "node:module";
import {
    mkdirSync,
    mkdtempSync,
    readFileSync,
    realpathSync,
    rmSync,
    writeFileSync,
} from "node:fs";
import { dirname, join, resolve } from "node:path";

const require = createRequire(import.meta.url);

describe("security dependency compatibility", () => {
    it("preserves Vue Test Utils formatted HTML output", () => {
        const wrapper = mount({
            template:
                "<section><ul><li>first</li><li>second</li></ul></section>",
        });
        try {
            const html = wrapper.html({ format: true });
            expect(html).toContain("\n");
            expect(html).toContain("<li>first</li>");
            expect(html).toContain("<li>second</li>");
            expect(wrapper.findAll("li")).toHaveLength(2);
        } finally {
            wrapper.unmount();
        }
    });

    it("preserves beautifier glob expansion, ignored paths and EditorConfig matching", () => {
        const parent = resolve("storage/framework/testing");
        mkdirSync(parent, { recursive: true });
        const directory = mkdtempSync(join(parent, "dependency-compat-"));
        const beautifierRequire = createRequire(
            require.resolve("js-beautify/package.json"),
        );
        const editorconfig = beautifierRequire("editorconfig");
        try {
            mkdirSync(join(directory, "nested"));
            mkdirSync(join(directory, "node_modules"));
            mkdirSync(join(directory, ".git"));
            writeFileSync(
                join(directory, ".editorconfig"),
                "root = true\n[*.{js,css}]\nindent_style = space\nindent_size = 3\n[nested/**.{js,css}]\nindent_size = 2\n",
            );
            writeFileSync(
                join(directory, "first.js"),
                "const first={value:1};",
            );
            writeFileSync(
                join(directory, "nested/second.js"),
                "const second={value:2};",
            );
            for (const excluded of ["node_modules", ".git"]) {
                writeFileSync(
                    join(directory, excluded, "ignored.js"),
                    "const IGNORED_MARKER=1;",
                );
            }
            expect(
                editorconfig.parseSync(join(directory, "first.js")).indent_size,
            ).toBe(3);
            expect(
                editorconfig.parseSync(join(directory, "nested/second.js"))
                    .indent_size,
            ).toBe(2);
            const result = spawnSync(
                process.execPath,
                [
                    require.resolve("js-beautify/js/bin/js-beautify.js"),
                    "--editorconfig",
                    "**/*.js",
                ],
                { cwd: directory, encoding: "utf8" },
            );
            expect(result.status).toBe(0);
            expect(readFileSync(join(directory, "first.js"), "utf8")).toContain(
                "\n   value: 1",
            );
            expect(
                readFileSync(join(directory, "nested/second.js"), "utf8"),
            ).toContain("\n  value: 2");
            expect(result.stdout.includes("IGNORED_MARKER")).toBe(false);
            for (const excluded of ["node_modules", ".git"]) {
                expect(
                    readFileSync(
                        join(directory, excluded, "ignored.js"),
                        "utf8",
                    ),
                ).toBe("const IGNORED_MARKER=1;");
            }
        } finally {
            // Delete only the test directory created directly under our testing root.
            if (dirname(realpathSync(directory)) !== realpathSync(parent)) {
                throw new Error("Unexpected compatibility fixture path.");
            }
            rmSync(directory, { recursive: true });
        }
    });

    it.each([false, true])(
        "preserves concurrently named commands and failure status (failure=%s)",
        (fails) => {
            const cli = join(
                dirname(require.resolve("concurrently/package.json")),
                "dist/bin/concurrently.js",
            );
            const result = spawnSync(
                process.execPath,
                [
                    cli,
                    "--names",
                    "first,second",
                    "--success",
                    "all",
                    'node -e "console.log(123)"',
                    fails
                        ? 'node -e "process.exit(7)"'
                        : 'node -e "console.log(456)"',
                ],
                { encoding: "utf8" },
            );
            expect(result.status).toBe(fails ? 1 : 0);
            expect(result.stdout).toContain("[first]");
            expect(result.stdout).toContain("[second]");
        },
    );
});
