<script setup>
import { notifyRequestError } from "@/Composables/useRequestError";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { route } from "@/Utils/routes";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import Button from "primevue/button";
import Column from "primevue/column";
import ConfirmDialog from "primevue/confirmdialog";
import DataTable from "primevue/datatable";
import DatePicker from "primevue/datepicker";
import Dialog from "primevue/dialog";
import Tag from "primevue/tag";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { trans } from "laravel-vue-i18n";
import { computed, ref } from "vue";

/**
 * @typedef {Object} ExecutionReadinessReason
 * @property {string} code
 * @property {number|null} purchase_requisition_item_id
 * @property {number|null} item_id
 * @property {string|null} item_number
 * @property {Object<string, number|string>} parameters
 */
/**
 * @typedef {Object} ExecutionReadinessResult
 * @property {number} purchase_requisition_id
 * @property {boolean} is_ready
 * @property {ExecutionReadinessReason[]} blocking_reasons
 * @property {ExecutionReadinessReason[]} warnings
 * @property {Array<Object>} item_results
 * @property {string} checked_at
 */
/**
 * @typedef {Object} SupplierCandidateSource
 * @property {number} item_supplier_id
 * @property {number} item_id
 * @property {string} item_number
 * @property {string} item_name
 * @property {boolean} preferred
 * @property {number} priority
 * @property {number|null} lead_time_days
 * @property {string|null} unit_price
 * @property {string|null} currency
 * @property {string} purchase_unit
 * @property {string} conversion_factor
 * @property {string|null} minimum_order_quantity
 * @property {string|null} order_multiple
 * @property {string|null} valid_from
 * @property {string|null} valid_until
 */
/**
 * @typedef {Object} SupplierCandidate
 * @property {number} supplier_id
 * @property {string} supplier_code
 * @property {string} supplier_name
 * @property {SupplierCandidateSource[]} sources
 */
/**
 * Beszerzési igénytétel forrása.
 * @typedef {Object} RequisitionSource
 * @property {number} id A forrás azonosítója.
 * @property {number|string} quantity A forrás mennyisége.
 * @property {{customer_order_item: {customer_order: {order_number: string}|null}|null}|null} material_requirement A kapcsolódó anyagszükséglet.
 */
/**
 * Beszerzési igénytétel.
 * @typedef {Object} PurchaseRequisitionItem
 * @property {number} id A tétel azonosítója.
 * @property {number|string} quantity Az igényelt mennyiség.
 * @property {number|string} planned_quantity A planning source mennyisége.
 * @property {number|string} replenishment_excess_quantity A supplier policy miatti többlet.
 * @property {string|null} replenishment_strategy A levezetett quantity strategy.
 * @property {string|null} replenishment_minimum_order_quantity A számításkori MOQ snapshot.
 * @property {string|null} replenishment_order_multiple A számításkori order multiple snapshot.
 * @property {string|null} replenishment_calculated_at Az utolsó számítás időpontja.
 * @property {string} unit A mértékegység.
 * @property {string} status A tétel állapota.
 * @property {number|null} material_requirement_id A közvetlen anyagszükséglet azonosítója.
 * @property {{item_number: string, name: string}|null} item Az igényelt cikk.
 * @property {RequisitionSource[]} sources A tétel forrásai.
 * @property {Array<{id: number, quantity: string, supply_proposal_id: number}>} proposal_sources Az approved Proposal execution source-ok.
 */
/**
 * Megjelenített beszerzési igény.
 * @typedef {Object} PurchaseRequisitionRecord
 * @property {number} id Az igény azonosítója.
 * @property {string} requisition_number Az igény száma.
 * @property {string} status Az igény állapota.
 * @property {PurchaseRequisitionItem[]} items Az igény tételei.
 */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {PurchaseRequisitionRecord} purchaseRequisition A megjelenített beszerzési igény.
 * @property {SupplierCandidate[]} supplierCandidates A minden PR tételhez alkalmas beszállítók.
 * @property {boolean} canSelectSupplier A felhasználó supplier-választási jogosultsága.
 * @property {boolean} canCalculateReplenishment A felhasználó quantity-számítási jogosultsága.
 * @property {ExecutionReadinessResult} executionReadiness Az aktuális, nem perzisztált execution readiness eredmény.
 */
/** @type {Props} */
const props = defineProps({
    purchaseRequisition: Object,
    supplierCandidates: Array,
    canSelectSupplier: Boolean,
    canCalculateReplenishment: Boolean,
    executionReadiness: Object,
});
const confirm = useConfirm();
const toast = useToast();
const poDialogVisible = ref(false);
const supplierDialogVisible = ref(false);
const approving = ref(false);
const generating = ref(false);
const selectingSupplier = ref(false);
const calculatingReplenishment = ref(false);
const form = useForm({
    expected_delivery_date: null,
});
const supplierForm = useForm({ supplier_id: null });
const actionPending = computed(
    () =>
        approving.value ||
        generating.value ||
        selectingSupplier.value ||
        calculatingReplenishment.value,
);
const canApprove = computed(() =>
    ["draft", "requested"].includes(props.purchaseRequisition.status),
);
const canShowGeneratePo = computed(
    () => props.purchaseRequisition.status === "approved",
);
const canGeneratePo = computed(
    () => canShowGeneratePo.value && props.executionReadiness.is_ready,
);
const canOpenSupplierSelection = computed(
    () =>
        props.canSelectSupplier &&
        props.purchaseRequisition.status === "draft" &&
        props.purchaseRequisition.supplier_id == null,
);
const canCalculateReplenishment = computed(
    () =>
        props.canCalculateReplenishment &&
        props.purchaseRequisition.status === "draft" &&
        props.purchaseRequisition.supplier_id != null,
);
const hasProposalSources = computed(() =>
    props.purchaseRequisition.items.some(
        (item) => item.proposal_sources?.length > 0,
    ),
);
const severity = (value) =>
    ({
        approved: "success",
        requested: "info",
        ordered: "warn",
        cancelled: "danger",
    })[value] || "secondary";
const number = (value) => Number(value || 0).toFixed(3);
const valueOrDash = (value) => value ?? "-";
const validity = (source) =>
    `${source.valid_from || "-"} – ${source.valid_until || "-"}`;
const readinessReasonText = (reason) =>
    trans(
        `procurement.execution_readiness.reasons.${reason.code.toLowerCase()}`,
        reason.parameters || {},
    );
const approve = () =>
    confirm.require({
        message: trans(
            "procurement.purchase_requisitions.confirm_approve_message",
            {
                name: props.purchaseRequisition.requisition_number,
            },
        ),
        header: trans(
            "procurement.purchase_requisitions.confirm_approve_header",
        ),
        icon: "pi pi-check",
        accept: () => {
            if (actionPending.value) {
                return;
            }

            approving.value = true;
            router.patch(
                route(
                    "admin.purchase-requisitions.approve",
                    props.purchaseRequisition.id,
                ),
                {},
                {
                    onError: (error) => {
                        notifyRequestError(toast, error);
                    },
                    onFinish: () => {
                        approving.value = false;
                    },
                },
            );
        },
    });
const generatePo = () => {
    if (!canGeneratePo.value || actionPending.value) {
        return;
    }

    generating.value = true;
    form.post(
        route(
            "admin.purchase-requisitions.generate-purchase-order",
            props.purchaseRequisition.id,
        ),
        {
            preserveScroll: true,
            onError: (error) => {
                notifyRequestError(toast, error);
            },
            onFinish: () => {
                generating.value = false;
            },
        },
    );
};
/** @param {SupplierCandidate} candidate */
const selectSupplier = (candidate) => {
    if (actionPending.value) {
        return;
    }

    selectingSupplier.value = true;
    supplierForm.supplier_id = candidate.supplier_id;
    supplierForm.patch(
        route(
            "admin.purchase-requisitions.select-supplier",
            props.purchaseRequisition.id,
        ),
        {
            preserveScroll: true,
            onSuccess: () => {
                supplierDialogVisible.value = false;
            },
            onError: (error) => {
                notifyRequestError(toast, error);
            },
            onFinish: () => {
                selectingSupplier.value = false;
            },
        },
    );
};
const calculateReplenishment = () => {
    if (actionPending.value) {
        return;
    }

    calculatingReplenishment.value = true;
    router.patch(
        route(
            "admin.purchase-requisitions.calculate-replenishment",
            props.purchaseRequisition.id,
        ),
        {},
        {
            preserveScroll: true,
            onError: (error) => {
                notifyRequestError(toast, error);
            },
            onFinish: () => {
                calculatingReplenishment.value = false;
            },
        },
    );
};
</script>

<template>
    <Head :title="purchaseRequisition.requisition_number" />
    <AdminLayout>
        <ConfirmDialog />
        <div class="space-y-4">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="space-y-2">
                    <Link
                        :href="route('admin.purchase-requisitions.index')"
                        class="text-sm text-blue-700 hover:underline"
                        >{{
                            trans(
                                "procurement.purchase_requisitions.actions.back_to_requisitions",
                            )
                        }}</Link
                    >
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-semibold">
                            {{ purchaseRequisition.requisition_number }}
                        </h1>
                        <Tag
                            :value="
                                trans(`status.${purchaseRequisition.status}`)
                            "
                            :severity="severity(purchaseRequisition.status)"
                        />
                    </div>
                    <div
                        class="flex flex-wrap gap-x-5 gap-y-1 text-sm text-slate-600"
                    >
                        <span>{{
                            purchaseRequisition.supplier?.name ||
                            trans(
                                "planning.supply_proposals.supplier_not_selected",
                            )
                        }}</span>
                        <span v-if="purchaseRequisition.required_at">{{
                            purchaseRequisition.required_at.slice(0, 10)
                        }}</span>
                        <span v-if="purchaseRequisition.proposed_supply_at">{{
                            purchaseRequisition.proposed_supply_at.slice(0, 10)
                        }}</span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button
                        v-if="canOpenSupplierSelection"
                        type="button"
                        :label="
                            trans(
                                'procurement.supplier_selection.actions.select',
                            )
                        "
                        icon="pi pi-users"
                        outlined
                        :disabled="actionPending"
                        @click="supplierDialogVisible = true"
                    />
                    <Button
                        v-if="canCalculateReplenishment"
                        type="button"
                        :label="
                            trans('procurement.replenishment.actions.calculate')
                        "
                        icon="pi pi-calculator"
                        outlined
                        :loading="calculatingReplenishment"
                        :disabled="actionPending"
                        @click="calculateReplenishment"
                    />
                    <Button
                        v-if="canApprove"
                        type="button"
                        :label="trans('actions.approve')"
                        icon="pi pi-check"
                        severity="success"
                        :loading="approving"
                        :disabled="actionPending"
                        @click="approve"
                    />
                    <Button
                        v-if="canShowGeneratePo"
                        type="button"
                        :label="
                            trans(
                                'procurement.purchase_requisitions.actions.generate_purchase_order',
                            )
                        "
                        icon="pi pi-shopping-cart"
                        outlined
                        :loading="generating"
                        :disabled="actionPending || !canGeneratePo"
                        @click="poDialogVisible = true"
                    />
                </div>
            </div>
            <section
                class="rounded border border-slate-200 bg-white p-4"
                data-test="execution-readiness"
            >
                <div
                    class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2 class="text-lg font-semibold">
                            {{ trans("procurement.execution_readiness.title") }}
                        </h2>
                        <p class="text-xs text-slate-500">
                            {{
                                trans(
                                    "procurement.execution_readiness.checked_at",
                                    {
                                        checked_at:
                                            executionReadiness.checked_at,
                                    },
                                )
                            }}
                        </p>
                    </div>
                    <span
                        class="rounded px-3 py-1 text-sm font-semibold"
                        :class="
                            executionReadiness.is_ready
                                ? 'bg-emerald-100 text-emerald-800'
                                : 'bg-red-100 text-red-800'
                        "
                    >
                        {{
                            executionReadiness.is_ready
                                ? trans("procurement.execution_readiness.ready")
                                : trans(
                                      "procurement.execution_readiness.not_ready",
                                  )
                        }}
                    </span>
                </div>
                <div
                    v-if="executionReadiness.blocking_reasons.length > 0"
                    class="mt-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-900"
                >
                    <div class="mb-1 font-semibold">
                        {{ trans("procurement.execution_readiness.blockers") }}
                    </div>
                    <ul class="list-disc space-y-1 pl-5">
                        <li
                            v-for="reason in executionReadiness.blocking_reasons"
                            :key="`blocker-${reason.code}-${reason.purchase_requisition_item_id || 'pr'}`"
                        >
                            {{ readinessReasonText(reason) }}
                        </li>
                    </ul>
                </div>
                <div
                    v-if="executionReadiness.warnings.length > 0"
                    class="mt-4 rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"
                >
                    <div class="mb-1 font-semibold">
                        {{ trans("procurement.execution_readiness.warnings") }}
                    </div>
                    <ul class="list-disc space-y-1 pl-5">
                        <li
                            v-for="reason in executionReadiness.warnings"
                            :key="`warning-${reason.code}-${reason.purchase_requisition_item_id || 'pr'}`"
                        >
                            {{ readinessReasonText(reason) }}
                        </li>
                    </ul>
                </div>
            </section>
            <div class="rounded border border-slate-200 bg-white p-4">
                <DataTable :value="purchaseRequisition.items" data-key="id">
                    <Column :header="trans('fields.item')"
                        ><template #body="{ data }"
                            >{{ data.item?.item_number }} -
                            {{ data.item?.name }}</template
                        ></Column
                    >
                    <Column
                        :header="
                            trans('procurement.replenishment.planned_quantity')
                        "
                        ><template #body="{ data }"
                            >{{ number(data.planned_quantity) }}
                            {{ data.unit }}</template
                        ></Column
                    >
                    <Column
                        :header="
                            trans(
                                'procurement.replenishment.requested_quantity',
                            )
                        "
                        ><template #body="{ data }"
                            >{{ number(data.quantity) }}
                            {{ data.unit }}</template
                        ></Column
                    >
                    <Column
                        :header="
                            trans('procurement.replenishment.excess_quantity')
                        "
                        ><template #body="{ data }"
                            >{{ number(data.replenishment_excess_quantity) }}
                            {{ data.unit }}</template
                        ></Column
                    >
                    <Column
                        :header="trans('procurement.supplier_selection.moq')"
                        ><template #body="{ data }">{{
                            valueOrDash(
                                data.replenishment_minimum_order_quantity,
                            )
                        }}</template></Column
                    >
                    <Column
                        :header="
                            trans(
                                'procurement.supplier_selection.order_multiple',
                            )
                        "
                        ><template #body="{ data }">{{
                            valueOrDash(data.replenishment_order_multiple)
                        }}</template></Column
                    >
                    <Column
                        :header="trans('procurement.replenishment.strategy')"
                        ><template #body="{ data }">{{
                            data.replenishment_strategy
                                ? trans(
                                      `procurement.replenishment.strategies.${data.replenishment_strategy}`,
                                  )
                                : "-"
                        }}</template></Column
                    >
                    <Column field="status" :header="trans('fields.status')"
                        ><template #body="{ data }"
                            ><Tag
                                :value="
                                    trans(`status.${data.status}`)
                                " /></template
                    ></Column>
                    <Column :header="trans('fields.sources')"
                        ><template #body="{ data }">{{
                            (data.sources?.length ||
                                (data.material_requirement_id ? 1 : 0)) +
                            (data.proposal_sources?.length || 0)
                        }}</template></Column
                    >
                </DataTable>
            </div>
            <div
                v-if="hasProposalSources"
                class="rounded border border-slate-200 bg-white p-4"
            >
                <h2 class="mb-3 text-lg font-semibold">
                    {{
                        trans(
                            "procurement.purchase_requisitions.source_proposals.title",
                        )
                    }}
                </h2>
                <div
                    v-for="item in purchaseRequisition.items"
                    :key="`proposal-${item.id}`"
                    class="mb-4 last:mb-0"
                >
                    <div class="mb-2 text-sm font-medium">
                        {{ item.item?.item_number }} - {{ item.item?.name }}
                    </div>
                    <DataTable
                        :value="item.proposal_sources || []"
                        data-key="id"
                    >
                        <Column
                            field="supply_proposal_id"
                            :header="
                                trans(
                                    'procurement.purchase_requisitions.source_proposals.proposal',
                                )
                            "
                        />
                        <Column :header="trans('fields.quantity')"
                            ><template #body="{ data }"
                                >{{ number(data.quantity) }}
                                {{ item.unit }}</template
                            ></Column
                        >
                    </DataTable>
                </div>
            </div>
            <div class="rounded border border-slate-200 bg-white p-4">
                <h2 class="mb-3 text-lg font-semibold">
                    {{
                        trans(
                            "procurement.purchase_requisitions.source_requirements.title",
                        )
                    }}
                </h2>
                <div
                    v-for="item in purchaseRequisition.items"
                    :key="item.id"
                    class="mb-4 last:mb-0"
                >
                    <div class="mb-2 text-sm font-medium">
                        {{ item.item?.item_number }} - {{ item.item?.name }}
                    </div>
                    <DataTable :value="item.sources || []" data-key="id">
                        <Column :header="trans('fields.customer_order')"
                            ><template #body="{ data }">{{
                                data.material_requirement?.customer_order_item
                                    ?.customer_order?.order_number || "-"
                            }}</template></Column
                        >
                        <Column :header="trans('fields.quantity')"
                            ><template #body="{ data }">{{
                                number(data.quantity)
                            }}</template></Column
                        >
                    </DataTable>
                </div>
            </div>
        </div>
        <Dialog
            v-model:visible="supplierDialogVisible"
            modal
            :header="trans('procurement.supplier_selection.eligible_suppliers')"
            class="w-[min(72rem,calc(100vw-2rem))]"
        >
            <p class="mb-4 text-sm text-slate-600">
                {{ trans("procurement.supplier_selection.manual_notice") }}
            </p>
            <div
                v-if="supplierCandidates.length === 0"
                class="rounded border border-amber-200 bg-amber-50 p-4 text-amber-900"
            >
                {{ trans("procurement.supplier_selection.no_eligible") }}
            </div>
            <div v-else class="space-y-4">
                <section
                    v-for="candidate in supplierCandidates"
                    :key="candidate.supplier_id"
                    class="rounded border border-slate-200 p-4"
                >
                    <div
                        class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <div class="font-semibold">
                                {{ candidate.supplier_code }} –
                                {{ candidate.supplier_name }}
                            </div>
                            <div class="text-xs text-slate-500">
                                {{
                                    trans(
                                        "procurement.supplier_selection.common_source_notice",
                                    )
                                }}
                            </div>
                        </div>
                        <Button
                            type="button"
                            :label="
                                trans(
                                    'procurement.supplier_selection.actions.choose',
                                )
                            "
                            icon="pi pi-check"
                            :loading="
                                selectingSupplier &&
                                supplierForm.supplier_id ===
                                    candidate.supplier_id
                            "
                            :disabled="actionPending"
                            @click="selectSupplier(candidate)"
                        />
                    </div>
                    <DataTable
                        :value="candidate.sources"
                        data-key="item_supplier_id"
                        scrollable
                    >
                        <Column :header="trans('fields.item')"
                            ><template #body="{ data }"
                                >{{ data.item_number }} –
                                {{ data.item_name }}</template
                            ></Column
                        >
                        <Column
                            :header="
                                trans(
                                    'procurement.supplier_selection.preferred',
                                )
                            "
                            ><template #body="{ data }"
                                ><Tag
                                    :value="
                                        data.preferred
                                            ? trans('common.yes')
                                            : trans('common.no')
                                    "
                                    :severity="
                                        data.preferred ? 'success' : 'secondary'
                                    " /></template
                        ></Column>
                        <Column
                            field="priority"
                            :header="
                                trans('procurement.supplier_selection.priority')
                            "
                        />
                        <Column
                            :header="
                                trans(
                                    'procurement.supplier_selection.lead_time',
                                )
                            "
                            ><template #body="{ data }">{{
                                valueOrDash(data.lead_time_days)
                            }}</template></Column
                        >
                        <Column
                            :header="
                                trans('procurement.supplier_selection.price')
                            "
                            ><template #body="{ data }"
                                >{{ valueOrDash(data.unit_price) }}
                                {{ data.currency || "" }}</template
                            ></Column
                        >
                        <Column
                            :header="
                                trans(
                                    'procurement.supplier_selection.purchase_unit',
                                )
                            "
                            ><template #body="{ data }"
                                >{{ data.purchase_unit }} ×
                                {{ data.conversion_factor }}</template
                            ></Column
                        >
                        <Column
                            :header="
                                trans('procurement.supplier_selection.moq')
                            "
                            ><template #body="{ data }">{{
                                valueOrDash(data.minimum_order_quantity)
                            }}</template></Column
                        >
                        <Column
                            :header="
                                trans(
                                    'procurement.supplier_selection.order_multiple',
                                )
                            "
                            ><template #body="{ data }">{{
                                valueOrDash(data.order_multiple)
                            }}</template></Column
                        >
                        <Column
                            :header="
                                trans('procurement.supplier_selection.validity')
                            "
                            ><template #body="{ data }">{{
                                validity(data)
                            }}</template></Column
                        >
                    </DataTable>
                </section>
            </div>
        </Dialog>
        <Dialog
            v-model:visible="poDialogVisible"
            modal
            :header="
                trans(
                    'procurement.purchase_requisitions.actions.generate_purchase_order',
                )
            "
            class="w-[min(36rem,calc(100vw-2rem))]"
        >
            <form class="space-y-4" @submit.prevent="generatePo">
                <p class="text-sm text-surface-600 dark:text-surface-300">
                    {{ trans("fields.supplier") }}:
                    {{ purchaseRequisition.supplier?.name || "-" }}
                </p>
                <DatePicker
                    v-model="form.expected_delivery_date"
                    date-format="yy-mm-dd"
                    :placeholder="trans('fields.expected_delivery')"
                    :disabled="generating"
                    class="w-full"
                />
                <ul
                    v-if="form.errors.execution_readiness"
                    class="list-disc space-y-1 pl-5 text-sm text-red-600"
                >
                    <li
                        v-for="message in Array.isArray(
                            form.errors.execution_readiness,
                        )
                            ? form.errors.execution_readiness
                            : [form.errors.execution_readiness]"
                        :key="message"
                    >
                        {{ message }}
                    </li>
                </ul>
                <div class="flex justify-end gap-2">
                    <Button
                        type="button"
                        :label="trans('actions.cancel')"
                        severity="secondary"
                        outlined
                        :disabled="generating"
                        @click="poDialogVisible = false"
                    /><Button
                        type="submit"
                        :label="trans('actions.generate')"
                        icon="pi pi-check"
                        :loading="generating"
                        :disabled="actionPending"
                    />
                </div>
            </form>
        </Dialog>
    </AdminLayout>
</template>
