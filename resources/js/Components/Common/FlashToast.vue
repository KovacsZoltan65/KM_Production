<script setup>
import { router, usePage } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import { onBeforeUnmount, onMounted, watch } from "vue";

const page = usePage();
const toast = useToast();
let lastSignature = "";

const messageTypes = [
    { key: "error", severity: "error", life: 5000 },
    { key: "warning", severity: "warn", life: 4000 },
    { key: "success", severity: "success", life: 2500 },
    { key: "info", severity: "info", life: 3500 },
];

/**
 * Megjeleníti az aktuális flash üzeneteket, azonos prop-frissítést csak egyszer.
 *
 * @param {Record<string, string|null>|null|undefined} flash A megosztott flash prop.
 * @returns {void}
 */
const showFlash = (flash) => {
    const messages = messageTypes
        .map((type) => ({ ...type, message: flash?.[type.key] }))
        .filter((type) => typeof type.message === "string" && type.message);
    const signature = JSON.stringify(
        messages.map(({ key, message }) => [key, message]),
    );

    if (!signature || signature === "[]") {
        lastSignature = "";
        return;
    }

    if (signature === lastSignature) {
        return;
    }

    lastSignature = signature;
    messages.forEach(({ severity, message, life }) => {
        toast.add({ severity, summary: message, life });
    });
};

watch(() => page.props.flash, showFlash, { deep: true });
onMounted(() => showFlash(page.props.flash));

const removeValidationListener = router.on("error", () => {
    toast.add({
        severity: "error",
        summary: trans("notifications.error.validation"),
        life: 5000,
    });
});

const removeNetworkListener = router.on("networkError", (event) => {
    if (event?.detail?.error?.name === "AbortError") {
        return;
    }

    toast.add({
        severity: "error",
        summary: trans("notifications.error.network"),
        life: 5000,
    });
});

onBeforeUnmount(() => {
    removeValidationListener();
    removeNetworkListener();
});
</script>

<template>
    <Toast data-test="global-flash-toast" />
</template>
