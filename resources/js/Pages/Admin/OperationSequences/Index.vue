<script setup>
import AdminActionButtons from "@/Components/Admin/AdminActionButtons.vue";
import AdminPageHeader from "@/Components/Admin/AdminPageHeader.vue";
import AdminSearchBar from "@/Components/Admin/AdminSearchBar.vue";
import AdminStatusBadge from "@/Components/Admin/AdminStatusBadge.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import SequenceStepsEditor from "@/Pages/Admin/OperationSequences/Partials/SequenceStepsEditor.vue";
import { route } from "@/Utils/routes";
import { Head, router, usePage } from "@inertiajs/vue3";
import Button from "primevue/button";
import Column from "primevue/column";
import ConfirmDialog from "primevue/confirmdialog";
import DataTable from "primevue/datatable";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Textarea from "primevue/textarea";
import Toast from "primevue/toast";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { trans } from "laravel-vue-i18n";
import { computed, onMounted, reactive, ref, watch } from "vue";

/** @typedef {{id: number, item_number: string, name: string, unit: string}} ItemOption */
/** @typedef {{id: number, code: string, name: string}} CodedOption */
/** @typedef {{step_order: number, operation_type_id: number, factory_unit_id: number|null, professional_role_id: number|null, setup_time_minutes: number, cycle_time_minutes: number, instructions: string|null}} SequenceStepRecord */
/** @typedef {{id: number, item_id: number, version: number, name: string, description: string|null, is_active: boolean, item: {item_number: string, name: string}|null, steps: SequenceStepRecord[]}} OperationSequenceRecord */
/**
 * Lapozott Inertia-adathalmaz.
 * @typedef {Object} PaginatedResult
 * @property {OperationSequenceRecord[]} data Az aktuális oldal műveletsorai.
 * @property {number} current_page Az aktuális oldalszám.
 * @property {number} per_page Az oldalankénti elemszám.
 * @property {number} total A teljes elemszám.
 * @property {number} last_page Az utolsó oldalszám.
 */
/**
 * Listaoldal szerveroldali szűrői.
 * @typedef {Object} PageFilters
 * @property {string} [search] A keresőkifejezés.
 * @property {number|string} [per_page] Az oldalankénti elemszám.
 * @property {string} [sort] A rendezett mező.
 * @property {'asc'|'desc'} [direction] A rendezés iránya.
 * @property {string|number|null} [status] Az állapotszűrő.
 */
/**
 * A komponens bemeneti tulajdonságai.
 * @typedef {Object} Props
 * @property {PaginatedResult} records A lapozott műveletsorok.
 * @property {PageFilters} filters Az aktív listaszűrők.
 * @property {ItemOption[]} itemOptions A választható cikkek.
 * @property {CodedOption[]} operationTypeOptions A választható művelettípusok.
 * @property {CodedOption[]} factoryUnitOptions A választható gyártóegységek.
 * @property {CodedOption[]} professionalRoleOptions A választható szakmai szerepkörök.
 */
/** @type {Props} */
const props = defineProps({
    records: Object,
    filters: Object,
    itemOptions: Array,
    operationTypeOptions: Array,
    factoryUnitOptions: Array,
    professionalRoleOptions: Array,
});

const page = usePage();
const toast = useToast();
const confirm = useConfirm();
const dialogVisible = ref(false);
const editingRecord = ref(null);
const search = ref(props.filters.search || "");
const perPage = ref(
    Number(props.filters.per_page || props.records.per_page || 10),
);
const sortField = ref(props.filters.sort || "id");
const sortOrder = ref((props.filters.direction || "asc") === "desc" ? -1 : 1);
const errors = ref({});
const form = reactive({
    item_id: null,
    version: 1,
    name: "",
    description: "",
    is_active: true,
    steps: [],
});

const itemChoices = computed(() =>
    props.itemOptions.map((item) => ({
        ...item,
        label: `${item.item_number} - ${item.name}`,
    })),
);
const dialogTitle = computed(() =>
    editingRecord.value
        ? trans("operation_sequences.dialogs.edit")
        : trans("operation_sequences.dialogs.create"),
);

const resetForm = () => {
    Object.assign(form, {
        item_id: null,
        version: 1,
        name: "",
        description: "",
        is_active: true,
        steps: [],
    });
    errors.value = {};
};

const normalizeSteps = (record) =>
    (record.steps || []).map((step) => ({
        step_order: step.step_order,
        operation_type_id: step.operation_type_id,
        factory_unit_id: step.factory_unit_id,
        professional_role_id: step.professional_role_id,
        estimated_duration_minutes: step.estimated_duration_minutes,
        requires_quality_check: Boolean(step.requires_quality_check),
        instructions: step.instructions,
    }));

const openCreate = () => {
    editingRecord.value = null;
    resetForm();
    dialogVisible.value = true;
};

const openEdit = (record) => {
    editingRecord.value = record;
    Object.assign(form, {
        item_id: record.item_id,
        version: record.version,
        name: record.name,
        description: record.description,
        is_active: Boolean(record.is_active),
        steps: normalizeSteps(record),
    });
    errors.value = {};
    dialogVisible.value = true;
};

const query = (pageNumber = props.records.current_page || 1) => ({
    search: search.value || undefined,
    sort: sortField.value || undefined,
    direction: sortOrder.value === -1 ? "desc" : "asc",
    per_page: perPage.value,
    page: pageNumber,
});

const reload = (pageNumber = 1) => {
    router.get(route("admin.operation-sequences.index"), query(pageNumber), {
        preserveState: true,
        replace: true,
    });
};

const submit = () => {
    errors.value = {};
    const payload = { ...form, steps: [...form.steps] };
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
            route("admin.operation-sequences.update", editingRecord.value.id),
            payload,
            callbacks,
        );
        return;
    }

    router.post(route("admin.operation-sequences.store"), payload, callbacks);
};

const destroyRecord = (record) => {
    confirm.require({
        message: trans("operation_sequences.confirm.delete_message"),
        header: trans("operation_sequences.confirm.delete_header"),
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () =>
            router.delete(
                route("admin.operation-sequences.destroy", record.id),
                { preserveScroll: true },
            ),
    });
};

onMounted(() => {
    if (page.props.flash?.success) {
        toast.add({
            severity: "success",
            summary: page.props.flash.success,
            life: 2500,
        });
    }
});

watch(
    () => page.props.flash?.success,
    (message) => {
        if (message) {
            toast.add({ severity: "success", summary: message, life: 2500 });
        }
    },
);
</script>

<template>
    <Head :title="trans('operation_sequences.title')" />

    <AdminLayout>
        <Toast />
        <ConfirmDialog />

        <div class="space-y-4">
            <AdminPageHeader
                title=""
                title-key="operation_sequences.title"
                subtitle-key="operation_sequences.subtitle"
                create-label-key="operation_sequences.actions.create"
                @create="openCreate"
            />
            <AdminSearchBar
                v-model="search"
                v-model:per-page="perPage"
                @search="reload(1)"
            />

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
                <Column field="name" :header="trans('fields.name')" sortable />
                <Column field="item_id" :header="trans('fields.item')" sortable>
                    <template #body="{ data }"
                        >{{ data.item?.item_number }} -
                        {{ data.item?.name }}</template
                    >
                </Column>
                <Column
                    field="version"
                    :header="trans('fields.version')"
                    sortable
                />
                <Column :header="trans('operation_sequences.steps.title')">
                    <template #body="{ data }">{{
                        data.steps?.length || 0
                    }}</template>
                </Column>
                <Column
                    field="is_active"
                    :header="trans('fields.status')"
                    sortable
                >
                    <template #body="{ data }"
                        ><AdminStatusBadge :active="Boolean(data.is_active)"
                    /></template>
                </Column>
                <Column header="" body-style="text-align: right; width: 7rem">
                    <template #body="{ data }"
                        ><AdminActionButtons
                            @edit="openEdit(data)"
                            @delete="destroyRecord(data)"
                    /></template>
                </Column>
            </DataTable>
        </div>

        <Dialog
            v-model:visible="dialogVisible"
            modal
            :header="dialogTitle"
            class="w-[min(78rem,calc(100vw-2rem))]"
        >
            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label for="item_id" class="text-sm font-medium">{{
                            trans("fields.item")
                        }}</label>
                        <Select
                            id="item_id"
                            v-model="form.item_id"
                            :options="itemChoices"
                            option-label="label"
                            option-value="id"
                            filter
                            class="w-full"
                        />
                        <p v-if="errors.item_id" class="text-sm text-red-600">
                            {{ errors.item_id }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <label for="version" class="text-sm font-medium">{{
                            trans("fields.version")
                        }}</label>
                        <InputText
                            id="version"
                            v-model="form.version"
                            type="number"
                            class="w-full"
                        />
                        <p v-if="errors.version" class="text-sm text-red-600">
                            {{ errors.version }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <label for="name" class="text-sm font-medium">{{
                            trans("fields.name")
                        }}</label>
                        <InputText
                            id="name"
                            v-model="form.name"
                            class="w-full"
                        />
                        <p v-if="errors.name" class="text-sm text-red-600">
                            {{ errors.name }}
                        </p>
                    </div>
                    <label class="flex items-center gap-2 pt-8 text-sm">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4"
                        />
                        <span>{{ trans("status.active") }}</span>
                    </label>
                </div>
                <div class="space-y-2">
                    <label for="description" class="text-sm font-medium">{{
                        trans("fields.description")
                    }}</label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        rows="2"
                        class="w-full"
                    />
                </div>

                <SequenceStepsEditor
                    v-model="form.steps"
                    :operation-type-options="operationTypeOptions"
                    :factory-unit-options="factoryUnitOptions"
                    :professional-role-options="professionalRoleOptions"
                    :errors="errors"
                />

                <div class="flex justify-end gap-2 pt-2">
                    <Button
                        type="button"
                        :label="trans('actions.cancel')"
                        severity="secondary"
                        outlined
                        @click="dialogVisible = false"
                    />
                    <Button
                        type="submit"
                        :label="trans('actions.save')"
                        icon="pi pi-save"
                    />
                </div>
            </form>
        </Dialog>
    </AdminLayout>
</template>
