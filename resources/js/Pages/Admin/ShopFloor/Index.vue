<script setup>
import ShopFloorTaskCard from "@/Components/ShopFloorTaskCard.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";

/**
 * Műhelyszintű gyártási feladat.
 * @typedef {Object} ShopFloorTask
 * @property {number} id A feladat azonosítója.
 * @property {string} status A feladat állapota.
 * @property {{serial_number: string}|null} item_instance A gyártott példány.
 * @property {{order_number: string}|null} production_order A gyártási rendelés.
 * @property {{operation_type: {name: string}|null, factory_unit: {code: string}|null}|null} operation_sequence_step A végrehajtandó műveleti lépés.
 * @property {{name: string}|null} employee A hozzárendelt alkalmazott.
 */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {ShopFloorTask[]} tasks A megjelenített gyártási feladatok.
 */
/** @type {Props} */
defineProps({ tasks: Array });
</script>

<template>
    <Head :title="$t('shop_floor.title')" />
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ $t("shop_floor.title") }}
                </h1>
                <p class="text-sm text-slate-600">
                    {{ $t("shop_floor.subtitle") }}
                </p>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <ShopFloorTaskCard
                    v-for="task in tasks"
                    :key="task.id"
                    :task="task"
                />
            </div>
        </div>
    </AdminLayout>
</template>
