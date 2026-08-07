<script setup>
import AdminPageHeader from "@/Components/Admin/AdminPageHeader.vue";
import AdminSearchBar from "@/Components/Admin/AdminSearchBar.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import SupplyProposalStatusBadge from "@/Pages/Admin/SupplyProposals/Partials/SupplyProposalStatusBadge.vue";
import { route } from "@/Utils/routes";
import { Head, router } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import Button from "primevue/button";
import Column from "primevue/column";
import ConfirmDialog from "primevue/confirmdialog";
import DataTable from "primevue/datatable";
import Dialog from "primevue/dialog";
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Textarea from "primevue/textarea";
import { useConfirm } from "primevue/useconfirm";
import { computed, reactive, ref, watch } from "vue";

const props = defineProps({
    records: Object,
    filters: Object,
    itemOptions: Array,
    supplierOptionsByItem: Object,
    strategyOptions: Array,
    statusOptions: Array,
    abilities: Object,
});

const confirm = useConfirm();
const dialogVisible = ref(false);
const editingRecord = ref(null);
const errors = ref({});
const search = ref(props.filters.search || "");
const status = ref(props.filters.status || null);
const strategy = ref(props.filters.strategy || null);
const perPage = ref(
    Number(props.filters.per_page || props.records.per_page || 10),
);
const sortField = ref(props.filters.sort || "id");
const sortOrder = ref((props.filters.direction || "desc") === "asc" ? 1 : -1);

const form = reactive({
    strategy: "purchase",
    item_id: null,
    supplier_id: null,
    proposed_quantity: null,
    required_at: null,
    proposed_supply_at: null,
    reason_code: "manual_planning",
    notes: "",
});

const supplierOptions = computed(() =>
    form.strategy === "purchase" && form.item_id
        ? props.supplierOptionsByItem[String(form.item_id)] || []
        : [],
);
const selectedItem = computed(() =>
    props.itemOptions.find((item) => item.id === form.item_id),
);
const dialogTitle = computed(() =>
    trans(
        editingRecord.value
            ? "planning.supply_proposals.edit_title"
            : "planning.supply_proposals.create_title",
    ),
);

watch(
    () => form.item_id,
    (current, previous) => {
        if (previous !== null && current !== previous) form.supplier_id = null;
    },
);
watch(
    () => form.strategy,
    (value) => {
        if (value !== "purchase") form.supplier_id = null;
    },
);

const resetForm = () => {
    Object.assign(form, {
        strategy: "purchase",
        item_id: null,
        supplier_id: null,
        proposed_quantity: null,
        required_at: null,
        proposed_supply_at: null,
        reason_code: "manual_planning",
        notes: "",
    });
    errors.value = {};
};
const openCreate = () => {
    editingRecord.value = null;
    resetForm();
    dialogVisible.value = true;
};
const openEdit = (record) => {
    if (record.status !== "draft") return;
    editingRecord.value = record;
    Object.assign(form, {
        strategy: record.strategy,
        item_id: record.item_id,
        supplier_id: record.supplier_id,
        proposed_quantity: Number(record.proposed_quantity),
        required_at: record.required_at?.slice(0, 10) || null,
        proposed_supply_at: record.proposed_supply_at?.slice(0, 10) || null,
        reason_code: record.reason_code || "",
        notes: record.notes || "",
    });
    errors.value = {};
    dialogVisible.value = true;
};

const query = (page = 1) => ({
    search: search.value || undefined,
    status: status.value || undefined,
    strategy: strategy.value || undefined,
    per_page: perPage.value,
    sort: sortField.value,
    direction: sortOrder.value === 1 ? "asc" : "desc",
    page,
});
const reload = (page = 1) =>
    router.get(route("admin.supply-proposals.index"), query(page), {
        preserveState: true,
        replace: true,
    });
const submit = () => {
    errors.value = {};
    const callbacks = {
        preserveScroll: true,
        onSuccess: () => {
            dialogVisible.value = false;
            resetForm();
        },
        onError: (responseErrors) => {
            errors.value = responseErrors;
        },
    };
    if (editingRecord.value) {
        router.put(
            route("admin.supply-proposals.update", editingRecord.value.id),
            { ...form },
            callbacks,
        );
    } else {
        router.post(
            route("admin.supply-proposals.store"),
            { ...form },
            callbacks,
        );
    }
};

const transition = (record, action) =>
    confirm.require({
        message: trans(`planning.supply_proposals.confirm.${action}`),
        header: trans(`planning.supply_proposals.actions.${action}`),
        icon:
            action === "approve"
                ? "pi pi-check-circle"
                : "pi pi-exclamation-triangle",
        accept: () =>
            router.patch(
                route(`admin.supply-proposals.${action}`, record.id),
                {},
                { preserveScroll: true },
            ),
    });
const formatDate = (value) => value?.slice(0, 10) || "—";
const supplierLabel = (record) =>
    record.supplier
        ? `${record.supplier.code} - ${record.supplier.name}`
        : trans("planning.supply_proposals.supplier_not_selected");
</script>

<template>
    <Head :title="$t('planning.supply_proposals.title')" />
    <AdminLayout>
        <ConfirmDialog />
        <div class="space-y-4">
            <AdminPageHeader
                :title="$t('planning.supply_proposals.title')"
                :subtitle="$t('planning.supply_proposals.subtitle')"
                :create-label="
                    abilities.create
                        ? $t('planning.supply_proposals.create')
                        : null
                "
                @create="openCreate"
            />
            <AdminSearchBar
                v-model="search"
                v-model:per-page="perPage"
                @search="reload(1)"
            />
            <div
                class="flex flex-wrap gap-3 rounded border border-slate-200 bg-white p-3"
            >
                <Select
                    v-model="status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    show-clear
                    :placeholder="$t('fields.status')"
                    class="w-64"
                    @update:model-value="reload(1)"
                />
                <Select
                    v-model="strategy"
                    :options="strategyOptions"
                    option-label="label"
                    option-value="value"
                    show-clear
                    :placeholder="
                        $t('planning.supply_proposals.fields.strategy')
                    "
                    class="w-64"
                    @update:model-value="reload(1)"
                />
            </div>

            <DataTable
                :value="records.data"
                lazy
                paginator
                :rows="records.per_page"
                :first="(records.current_page - 1) * records.per_page"
                :total-records="records.total"
                :sort-field="sortField"
                :sort-order="sortOrder"
                data-key="id"
                class="rounded border border-slate-200 bg-white"
                @page="
                    (event) => {
                        perPage = event.rows;
                        reload(event.page + 1);
                    }
                "
                @sort="
                    (event) => {
                        sortField = event.sortField;
                        sortOrder = event.sortOrder;
                        reload(1);
                    }
                "
            >
                <Column field="item_id" :header="$t('fields.item')" sortable>
                    <template #body="{ data }"
                        >{{ data.item.item_number }} -
                        {{ data.item.name }}</template
                    >
                </Column>
                <Column
                    field="strategy"
                    :header="$t('planning.supply_proposals.fields.strategy')"
                    sortable
                >
                    <template #body="{ data }">{{
                        $t(
                            `planning.supply_proposals.strategy.${data.strategy}`,
                        )
                    }}</template>
                </Column>
                <Column field="supplier_id" :header="$t('fields.supplier')">
                    <template #body="{ data }">{{
                        supplierLabel(data)
                    }}</template>
                </Column>
                <Column
                    field="proposed_quantity"
                    :header="
                        $t('planning.supply_proposals.fields.proposed_quantity')
                    "
                    sortable
                >
                    <template #body="{ data }"
                        >{{ data.proposed_quantity }} {{ data.unit }}</template
                    >
                </Column>
                <Column
                    field="required_at"
                    :header="$t('planning.supply_proposals.fields.required_at')"
                    sortable
                >
                    <template #body="{ data }">{{
                        formatDate(data.required_at)
                    }}</template>
                </Column>
                <Column
                    field="proposed_supply_at"
                    :header="
                        $t(
                            'planning.supply_proposals.fields.proposed_supply_at',
                        )
                    "
                    sortable
                >
                    <template #body="{ data }">{{
                        formatDate(data.proposed_supply_at)
                    }}</template>
                </Column>
                <Column field="status" :header="$t('fields.status')" sortable>
                    <template #body="{ data }"
                        ><SupplyProposalStatusBadge :status="data.status"
                    /></template>
                </Column>
                <Column
                    field="created_by"
                    :header="$t('planning.supply_proposals.fields.created_by')"
                >
                    <template #body="{ data }">{{
                        data.creator?.name || "—"
                    }}</template>
                </Column>
                <Column header="" body-style="text-align:right;min-width:13rem">
                    <template #body="{ data }">
                        <div
                            class="flex justify-end gap-1"
                            :data-status-actions="data.status"
                        >
                            <Button
                                v-if="
                                    data.status === 'draft' && abilities.update
                                "
                                icon="pi pi-pencil"
                                text
                                rounded
                                :aria-label="$t('actions.edit')"
                                @click="openEdit(data)"
                            />
                            <Button
                                v-if="
                                    data.status === 'draft' && abilities.update
                                "
                                icon="pi pi-send"
                                severity="info"
                                text
                                rounded
                                :aria-label="
                                    $t(
                                        'planning.supply_proposals.actions.propose',
                                    )
                                "
                                @click="transition(data, 'propose')"
                            />
                            <Button
                                v-if="
                                    data.status === 'proposed' &&
                                    abilities.approve
                                "
                                icon="pi pi-check"
                                severity="success"
                                text
                                rounded
                                :aria-label="
                                    $t(
                                        'planning.supply_proposals.actions.approve',
                                    )
                                "
                                @click="transition(data, 'approve')"
                            />
                            <Button
                                v-if="
                                    data.status === 'proposed' &&
                                    abilities.approve
                                "
                                icon="pi pi-times"
                                severity="danger"
                                text
                                rounded
                                :aria-label="
                                    $t(
                                        'planning.supply_proposals.actions.reject',
                                    )
                                "
                                @click="transition(data, 'reject')"
                            />
                            <Button
                                v-if="
                                    ['draft', 'proposed', 'approved'].includes(
                                        data.status,
                                    ) && abilities.cancel
                                "
                                icon="pi pi-ban"
                                severity="secondary"
                                text
                                rounded
                                :aria-label="
                                    $t(
                                        'planning.supply_proposals.actions.cancel',
                                    )
                                "
                                @click="transition(data, 'cancel')"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog
            v-model:visible="dialogVisible"
            modal
            :header="dialogTitle"
            class="w-[min(48rem,calc(100vw-2rem))]"
        >
            <form
                class="space-y-4"
                data-test="supply-proposal-form"
                @submit.prevent="submit"
            >
                <p class="rounded bg-blue-50 p-3 text-sm text-blue-800">
                    {{ $t("planning.supply_proposals.manual_hint") }}
                </p>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="space-y-1"
                        ><span>{{
                            $t("planning.supply_proposals.fields.strategy")
                        }}</span>
                        <Select
                            v-model="form.strategy"
                            :options="strategyOptions"
                            option-label="label"
                            option-value="value"
                            class="w-full"
                        />
                        <small class="text-red-600">{{
                            errors.strategy
                        }}</small></label
                    >
                    <label class="space-y-1"
                        ><span>{{ $t("fields.item") }}</span>
                        <Select
                            v-model="form.item_id"
                            :options="itemOptions"
                            option-label="label"
                            option-value="id"
                            filter
                            class="w-full"
                        />
                        <small class="text-red-600">{{
                            errors.item_id
                        }}</small></label
                    >
                    <label v-if="form.strategy === 'purchase'" class="space-y-1"
                        ><span>{{ $t("fields.supplier") }}</span>
                        <Select
                            v-model="form.supplier_id"
                            :options="supplierOptions"
                            option-label="label"
                            option-value="id"
                            show-clear
                            :placeholder="
                                $t(
                                    'planning.supply_proposals.supplier_not_selected',
                                )
                            "
                            class="w-full"
                        />
                        <small class="text-red-600">{{
                            errors.supplier_id
                        }}</small></label
                    >
                    <label class="space-y-1"
                        ><span>{{
                            $t(
                                "planning.supply_proposals.fields.proposed_quantity",
                            )
                        }}</span>
                        <div class="flex items-center gap-2">
                            <InputNumber
                                v-model="form.proposed_quantity"
                                :min="0.001"
                                :max-fraction-digits="3"
                                class="flex-1"
                            />
                            <span>{{ selectedItem?.unit || "—" }}</span>
                        </div>
                        <small class="text-red-600">{{
                            errors.proposed_quantity
                        }}</small></label
                    >
                    <label class="space-y-1"
                        ><span>{{
                            $t("planning.supply_proposals.fields.required_at")
                        }}</span>
                        <InputText
                            v-model="form.required_at"
                            type="date"
                            class="w-full"
                        /><small class="text-red-600">{{
                            errors.required_at
                        }}</small></label
                    >
                    <label class="space-y-1"
                        ><span>{{
                            $t(
                                "planning.supply_proposals.fields.proposed_supply_at",
                            )
                        }}</span>
                        <InputText
                            v-model="form.proposed_supply_at"
                            type="date"
                            class="w-full"
                        /><small class="text-red-600">{{
                            errors.proposed_supply_at
                        }}</small></label
                    >
                    <label class="space-y-1 md:col-span-2"
                        ><span>{{
                            $t("planning.supply_proposals.fields.reason")
                        }}</span>
                        <InputText
                            v-model="form.reason_code"
                            class="w-full"
                        /><small class="text-red-600">{{
                            errors.reason_code
                        }}</small></label
                    >
                    <label class="space-y-1 md:col-span-2"
                        ><span>{{
                            $t("planning.supply_proposals.fields.notes")
                        }}</span>
                        <Textarea
                            v-model="form.notes"
                            rows="4"
                            class="w-full"
                        /><small class="text-red-600">{{
                            errors.notes
                        }}</small></label
                    >
                </div>
                <div class="flex justify-end gap-2">
                    <Button
                        type="button"
                        :label="$t('actions.cancel')"
                        severity="secondary"
                        @click="dialogVisible = false"
                    />
                    <Button type="submit" :label="$t('actions.save')" />
                </div>
            </form>
        </Dialog>
    </AdminLayout>
</template>
