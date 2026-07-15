<script setup>
import { Link } from "@inertiajs/vue3";
</script>

<template>
    <div
        class="grid min-h-screen place-items-center bg-slate-50 px-4 py-10 text-slate-900"
    >
        <div class="w-full max-w-md">
            <Link
                href="/"
                class="mb-6 flex items-center justify-center gap-3 font-semibold"
            >
                <span
                    class="grid h-9 w-9 place-items-center rounded bg-blue-600 text-sm text-white"
                    >KM</span
                >
                <span>KM Production</span>
            </Link>

            <div class="rounded border border-slate-200 bg-white p-6 shadow-sm">
                <slot />
            </div>
        </div>
    </div>
</template>
