import js from "@eslint/js";
import pluginVue from "eslint-plugin-vue";
import eslintConfigPrettier from "eslint-config-prettier";
import globals from "globals";

export default [
    {
        ignores: [
            "node_modules/**",
            "public/build/**",
            "dist/**",
            "coverage/**",
            "vendor/**",
        ],
    },

    js.configs.recommended,

    ...pluginVue.configs["flat/essential"],

    {
        files: ["**/*.{js,vue}"],

        languageOptions: {
            ecmaVersion: "latest",
            sourceType: "module",

            globals: {
                ...globals.browser,
                ...globals.node,
            },
        },

        rules: {
            // Inertia page components intentionally use
            // Index.vue, Show.vue, Dashboard.vue, etc.
            "vue/multi-word-component-names": "off",

            "vue/no-mutating-props": [
                "error",
                {
                    shallowOnly: true,
                },
            ],
        },
    },

    {
        files: ["tests/frontend/**/*.js"],

        rules: {
            // Test stubs intentionally use PrimeVue/Inertia names
            // such as Button, Select, Dialog, Link and Head.
            "vue/no-reserved-component-names": "off",
        },
    },

    // Mindig utolsó legyen:
    // kikapcsolja azokat az ESLint szabályokat,
    // amelyek összeakadhatnak a Prettierrel.
    eslintConfigPrettier,
];
