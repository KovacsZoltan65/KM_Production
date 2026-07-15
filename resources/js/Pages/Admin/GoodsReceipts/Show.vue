<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { route } from "@/Utils/routes";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import Button from "primevue/button";
import Column from "primevue/column";
import ConfirmDialog from "primevue/confirmdialog";
import DataTable from "primevue/datatable";
import Tag from "primevue/tag";
import Toast from "primevue/toast";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { computed, onMounted, watch } from "vue";

/**
 * Áruátvételi tétel.
 * @typedef {Object} GoodsReceiptItem
 * @property {number} id A tétel azonosítója.
 * @property {number|string} quantity Az átvett mennyiség.
 * @property {string|null} notes A tétel megjegyzése.
 * @property {{item_number: string, name: string}|null} item Az átvett cikk.
 * @property {{code: string, name: string}|null} location A bevételezési hely.
 * @property {{batch_number: string}|null} item_batch A kapcsolódó tételazonosító.
 */
/**
 * Megjelenített áruátvétel.
 * @typedef {Object} GoodsReceiptRecord
 * @property {number} id Az áruátvétel azonosítója.
 * @property {string} receipt_number Az áruátvétel száma.
 * @property {string} status Az áruátvétel állapota.
 * @property {{order_number: string, supplier: {name: string}|null}|null} purchase_order A kapcsolódó beszerzési rendelés.
 * @property {GoodsReceiptItem[]} items Az áruátvétel tételei.
 */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {GoodsReceiptRecord} goodsReceipt A megjelenített áruátvétel.
 */
/** @type {Props} */
const props = defineProps({ goodsReceipt: Object });
const page = usePage();
const toast = useToast();
const confirm = useConfirm();
const canPost = computed(() => props.goodsReceipt.status !== "posted");
const number = (value) => Number(value || 0).toFixed(3);
const severity = (value) =>
    ({ posted: "success", draft: "secondary" })[value] || "secondary";
const postReceipt = () =>
    confirm.require({
        message: trans("procurement.goods_receipts.confirm_post_message", {
            number: props.goodsReceipt.receipt_number,
        }),
        header: trans("procurement.goods_receipts.confirm_post_header"),
        icon: "pi pi-check",
        accept: () =>
            router.post(
                route("admin.goods-receipts.post", props.goodsReceipt.id),
            ),
    });
const flash = (message) =>
    message && toast.add({ severity: "success", summary: message, life: 2500 });
onMounted(() => flash(page.props.flash?.success));
watch(() => page.props.flash?.success, flash);
</script>

<template>
    <Head :title="goodsReceipt.receipt_number" />
    <AdminLayout>
        <Toast />
        <ConfirmDialog />
        <div class="space-y-4">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="space-y-2">
                    <Link
                        :href="route('admin.goods-receipts.index')"
                        class="text-sm text-blue-700 hover:underline"
                        >{{ $t("procurement.goods_receipts.back") }}</Link
                    >
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-semibold">
                            {{ goodsReceipt.receipt_number }}
                        </h1>
                        <Tag
                            :value="$t(`status.${goodsReceipt.status}`)"
                            :severity="severity(goodsReceipt.status)"
                        />
                    </div>
                    <p class="text-sm text-slate-600">
                        {{ goodsReceipt.purchase_order?.supplier?.name || "-" }}
                        /
                        {{ goodsReceipt.purchase_order?.order_number || "-" }}
                    </p>
                </div>
                <Button
                    v-if="canPost"
                    type="button"
                    :label="$t('procurement.goods_receipts.post')"
                    icon="pi pi-check"
                    severity="success"
                    @click="postReceipt"
                />
            </div>
            <div class="rounded border border-slate-200 bg-white p-4">
                <DataTable :value="goodsReceipt.items" data-key="id">
                    <Column :header="$t('fields.item')"
                        ><template #body="{ data }"
                            >{{ data.item?.item_number }} -
                            {{ data.item?.name }}</template
                        ></Column
                    >
                    <Column :header="$t('fields.location')"
                        ><template #body="{ data }"
                            >{{ data.location?.code }} -
                            {{ data.location?.name }}</template
                        ></Column
                    >
                    <Column :header="$t('fields.batch')"
                        ><template #body="{ data }">{{
                            data.item_batch?.batch_number || "-"
                        }}</template></Column
                    >
                    <Column :header="$t('fields.received')"
                        ><template #body="{ data }">{{
                            number(data.quantity)
                        }}</template></Column
                    >
                    <Column field="notes" :header="$t('fields.notes')" />
                </DataTable>
            </div>
        </div>
    </AdminLayout>
</template>
