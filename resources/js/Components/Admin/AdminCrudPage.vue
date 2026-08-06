<script setup>
import AdminActionButtons from "@/Components/Admin/AdminActionButtons.vue";
import AdminCrudField from "@/Components/Admin/AdminCrudField.vue";
import AdminPageHeader from "@/Components/Admin/AdminPageHeader.vue";
import AdminSearchBar from "@/Components/Admin/AdminSearchBar.vue";
import AdminStatusBadge from "@/Components/Admin/AdminStatusBadge.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { notifyRequestError } from "@/Composables/useRequestError";
import { route } from "@/Utils/routes";
import { Head, router } from "@inertiajs/vue3";
import axios from "axios";
import Button from "primevue/button";
import Column from "primevue/column";
import ConfirmDialog from "primevue/confirmdialog";
import DataTable from "primevue/datatable";
import Dialog from "primevue/dialog";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { trans } from "laravel-vue-i18n";
import { computed, nextTick, reactive, ref } from "vue";

/**
 * Választható listaelem.
 * @typedef {Object} SelectOption
 * @property {number|string} [id] Az elem azonosítója.
 * @property {number|string|boolean} [value] Az elem értéke.
 * @property {string} [label] Az elem felirata.
 * @property {string} [name] Az elem neve.
 * @property {string} [code] Az elem kódja.
 */
/**
 * Lapozott Inertia-adathalmaz.
 * @typedef {Object} PaginatedResult
 * @property {Object[]} data Az aktuális oldal rekordjai.
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
 * @property {string|null} title A(z) title bemeneti értéke.
 * @property {string|null} titleKey A(z) titleKey bemeneti értéke.
 * @property {string|null} subtitle A(z) subtitle bemeneti értéke.
 * @property {string|null} subtitleKey A(z) subtitleKey bemeneti értéke.
 * @property {string|null} routeName A(z) routeName bemeneti értéke.
 * @property {PaginatedResult} records A(z) records bemeneti értéke.
 * @property {PageFilters} filters A(z) filters bemeneti értéke.
 * @property {Object[]} columns A(z) columns bemeneti értéke.
 * @property {Object[]} fields A(z) fields bemeneti értéke.
 * @property {string|null} createLabel A(z) createLabel bemeneti értéke.
 * @property {string|null} createLabelKey A(z) createLabelKey bemeneti értéke.
 * @property {boolean} readOnly A(z) readOnly bemeneti értéke.
 * @property {Object.<string, SelectOption[]>} options A(z) options bemeneti értéke.
 */
/**
 * Újrafelhasználható CRUD-mező konfigurációja.
 * @typedef {Object} CrudField
 * @property {string} name A payload mezőneve.
 * @property {string} [type] A megjelenített mezőtípus.
 * @property {boolean} [immutableOnEdit] Szerkesztéskor látható, de nem küldhető azonosító.
 * @property {{type: string, parameters?: Object.<string, string>}} [generateCode] A közös generáló végpont típusa és formmező-paraméterei.
 */
/** @type {Props} */
const props = defineProps({
    title: { type: String, required: true },
    titleKey: { type: String, default: "" },
    subtitle: { type: String, default: "" },
    subtitleKey: { type: String, default: "" },
    routeName: { type: String, required: true },
    records: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    columns: { type: Array, required: true },
    fields: { type: Array, default: () => [] },
    createLabel: { type: String, default: "" },
    createLabelKey: { type: String, default: "" },
    readOnly: { type: Boolean, default: false },
    options: { type: Object, default: () => ({}) },
});

const toast = useToast();
const confirm = useConfirm();
const dialogVisible = ref(false);
const editingRecord = ref(null);
const search = ref(props.filters.search || "");
const perPage = ref(
    Number(props.filters.per_page || props.records.per_page || 10),
);
const sortField = ref(
    typeof props.filters?.sort === "string" ? props.filters.sort : "id",
);
const sortOrder = ref((props.filters.direction || "asc") === "desc" ? -1 : 1);
const form = reactive({});
const errors = ref({});
const generatedValues = reactive({});
const generatingFields = reactive({});
const submitting = ref(false);
const deletingRecordId = ref(null);

const resolvedTitle = computed(() =>
    props.titleKey ? trans(props.titleKey) : props.title,
);
const resolvedSubtitle = computed(() =>
    props.subtitleKey ? trans(props.subtitleKey) : props.subtitle,
);
const resolvedCreateLabel = computed(() =>
    props.createLabelKey
        ? trans(props.createLabelKey)
        : props.createLabel || trans("actions.create"),
);
const pageTitle = computed(() =>
    editingRecord.value
        ? trans("admin.crud.edit_title", { title: resolvedTitle.value })
        : resolvedCreateLabel.value,
);
const indexRoute = computed(() => `${props.routeName}.index`);
const storeRoute = computed(() => `${props.routeName}.store`);
const updateRoute = computed(() => `${props.routeName}.update`);
const destroyRoute = computed(() => `${props.routeName}.destroy`);

const fieldRows = computed(() => {
    const rows = [];

    props.fields.forEach((field) => {
        const layoutGroup = field.layoutGroup || field.layout?.group || null;

        const previousRow = rows.at(-1);

        if (
            layoutGroup &&
            previousRow?.layoutGroup === layoutGroup &&
            previousRow.fields.length < 3
        ) {
            previousRow.fields.push(field);
            return;
        }

        rows.push({
            layoutGroup,
            fields: [field],
        });
    });

    return rows;
});

const resetForm = () => {
    props.fields.forEach((field) => {
        form[field.name] =
            field.default ??
            (field.type === "multiselect"
                ? []
                : field.type === "checkbox"
                  ? false
                  : null);
    });
    Object.keys(generatedValues).forEach((key) => delete generatedValues[key]);
    Object.keys(generatingFields).forEach(
        (key) => delete generatingFields[key],
    );
    errors.value = {};
};

const openCreate = () => {
    editingRecord.value = null;
    resetForm();
    dialogVisible.value = true;
};

/**
 * A natív HTML dátummező számára megfelelő YYYY-MM-DD értéket készít.
 *
 * @param {unknown} value A backendből érkező dátumérték.
 * @returns {string|null} A normalizált dátum vagy null.
 */
const normalizeDateValue = (value) => {
    if (!value) return null;

    const normalizedValue = String(value).slice(0, 10);

    return /^\d{4}-\d{2}-\d{2}$/.test(normalizedValue) ? normalizedValue : null;
};

const openEdit = (record) => {
    editingRecord.value = record;

    props.fields.forEach((field) => {
        const value = record[field.name];

        if (field.type === "multiselect") {
            form[field.name] = value || [];
            return;
        }

        if (field.type === "date") {
            form[field.name] = normalizeDateValue(
                value ?? field.default ?? null,
            );
            return;
        }

        form[field.name] = value ?? field.default ?? null;
    });

    errors.value = {};
    dialogVisible.value = true;
};

const fieldForMode = (field) => ({
    ...field,
    disabled: Boolean(
        field.disabled || (editingRecord.value && field.immutableOnEdit),
    ),
});

const generationParameters = (field) =>
    Object.fromEntries(
        Object.entries(field.generateCode?.parameters || {}).map(
            ([parameter, formField]) => [parameter, form[formField]],
        ),
    );

const generateCode = async (field) => {
    if (editingRecord.value || generatingFields[field.name]) {
        return;
    }

    generatingFields[field.name] = true;
    errors.value = { ...errors.value, [field.name]: undefined };

    try {
        const response = await axios.get(
            route("admin.code-generation.show", field.generateCode.type),
            { params: generationParameters(field) },
        );
        form[field.name] = response.data.code;
        generatedValues[field.name] = response.data.code;
        toast.add({
            severity: "success",
            summary: trans("code_generation.messages.generated"),
            life: 2500,
        });
    } catch (error) {
        const responseErrors = error.response?.data?.errors || {};
        errors.value = {
            ...errors.value,
            [field.name]:
                responseErrors.item_type?.[0] ||
                responseErrors.type?.[0] ||
                trans("code_generation.errors.generation_failed"),
        };
        notifyRequestError(toast, error, {
            fallbackKey: "code_generation.errors.generation_failed",
        });
    } finally {
        generatingFields[field.name] = false;
    }
};

const query = (pageNumber = props.records.current_page || 1) => ({
    search: search.value || undefined,
    sort: sortField.value || undefined,
    direction: sortOrder.value === -1 ? "desc" : "asc",
    per_page: perPage.value,
    page: pageNumber,
});

const reload = (pageNumber = 1) => {
    router.get(route(indexRoute.value), query(pageNumber), {
        preserveState: true,
        replace: true,
    });
};

const onPage = (event) => {
    perPage.value = event.rows;
    reload(event.page + 1);
};

const onSort = (event) => {
    sortField.value = event.sortField;
    sortOrder.value = event.sortOrder;
    reload(1);
};

const submit = () => {
    errors.value = {};
    const payload = { ...form };
    const codeFields = props.fields.filter((field) => field.generateCode);

    if (editingRecord.value) {
        props.fields
            .filter((field) => field.immutableOnEdit)
            .forEach((field) => delete payload[field.name]);
    } else if (codeFields.length > 0) {
        payload._code_was_generated = codeFields.some(
            (field) =>
                generatedValues[field.name] !== undefined &&
                form[field.name] === generatedValues[field.name],
        );
    }

    submitting.value = true;
    const callbacks = {
        preserveScroll: true,
        onSuccess: () => {
            dialogVisible.value = false;
            resetForm();
        },
        onError: (responseErrors) => {
            const suggestion = responseErrors.code_suggestion;
            const generatedField = codeFields[0];

            if (suggestion && generatedField) {
                form[generatedField.name] = suggestion;
                generatedValues[generatedField.name] = suggestion;
                const { code_suggestion: ignored, ...visibleErrors } =
                    responseErrors;
                errors.value = visibleErrors;
                focusFirstInvalidField(visibleErrors);
                return;
            }

            errors.value = responseErrors;
            focusFirstInvalidField(responseErrors);
        },
        onFinish: () => {
            submitting.value = false;
        },
    };

    if (editingRecord.value) {
        router.put(
            route(updateRoute.value, editingRecord.value.id),
            payload,
            callbacks,
        );
        return;
    }

    router.post(route(storeRoute.value), payload, callbacks);
};

const destroyRecord = (record) => {
    if (deletingRecordId.value !== null) {
        return;
    }

    confirm.require({
        message: trans("admin.crud.confirm_delete_message"),
        header: trans("admin.crud.confirm_delete_header"),
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            deletingRecordId.value = record.id;
            router.delete(route(destroyRoute.value, record.id), {
                preserveScroll: true,
                onFinish: () => {
                    deletingRecordId.value = null;
                },
            });
        },
    });
};

/**
 * A modál első hibás mezőjére viszi a fókuszt.
 *
 * @param {Record<string, string|string[]>} responseErrors A szerver mezőhibái.
 * @returns {void}
 */
const focusFirstInvalidField = (responseErrors) => {
    const firstField = Object.keys(responseErrors).find(
        (field) => field !== "code_suggestion",
    );

    if (!firstField || typeof document === "undefined") {
        return;
    }

    nextTick(() => document.getElementById(firstField)?.focus());
};

const resolveValue = (record, column) => {
    if (column.format) {
        return column.format(record);
    }

    return column.field.split(".").reduce((value, key) => value?.[key], record);
};

const resolveColumnHeader = (column) =>
    column.headerKey ? trans(column.headerKey) : column.header;
</script>

<template>
    <Head :title="resolvedTitle" />

    <AdminLayout>
        <ConfirmDialog />

        <div class="space-y-4">
            <AdminPageHeader
                :title="resolvedTitle"
                :subtitle="resolvedSubtitle"
                :create-label="resolvedCreateLabel"
                :can-create="!readOnly"
                @create="openCreate"
            >
                <template v-if="$slots['header-actions']" #actions>
                    <slot name="header-actions" />
                </template>
            </AdminPageHeader>

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
                @page="onPage"
                @sort="onSort"
            >
                <Column
                    v-for="column in columns"
                    :key="column.field"
                    :field="column.sortField || column.field"
                    :header="resolveColumnHeader(column)"
                    :sortable="column.sortable !== false"
                >
                    <template #body="{ data }">
                        <AdminStatusBadge
                            v-if="column.type === 'status'"
                            :active="Boolean(resolveValue(data, column))"
                        />
                        <span v-else>{{ resolveValue(data, column) }}</span>
                    </template>
                </Column>

                <Column
                    v-if="!readOnly"
                    header=""
                    body-style="text-align: right; width: 7rem"
                >
                    <template #body="{ data }">
                        <AdminActionButtons
                            :deleting="deletingRecordId === data.id"
                            @edit="openEdit(data)"
                            @delete="destroyRecord(data)"
                        />
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog
            v-model:visible="dialogVisible"
            modal
            :header="pageTitle"
            class="w-[min(42rem,calc(100vw-2rem))]"
        >
            <form class="space-y-4" @submit.prevent="submit">
                <p
                    v-if="fields.some((field) => field.required)"
                    class="text-sm text-slate-500"
                >
                    <span class="font-medium text-red-500" aria-hidden="true"
                        >*</span
                    >
                    {{
                        trans("admin.crud.required_fields_hint")
                            .replace("*", "")
                            .trim()
                    }}
                </p>

                <div
                    v-for="(row, index) in fieldRows"
                    :key="
                        row.layoutGroup
                            ? `${row.layoutGroup}-${index}`
                            : row.fields[0].name
                    "
                    :class="{
                        'grid grid-cols-1 gap-4 md:grid-cols-2':
                            row.layoutGroup && row.fields.length === 2,
                        'grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3':
                            row.layoutGroup && row.fields.length >= 3,
                    }"
                    :data-layout-group="row.layoutGroup || undefined"
                >
                    <div
                        v-for="field in row.fields"
                        :key="field.name"
                        class="min-w-0"
                    >
                        <AdminCrudField
                            v-model="form[field.name]"
                            :field="fieldForMode(field)"
                            :error="errors[field.name]"
                            :options="options"
                        >
                            <template
                                v-if="field.generateCode && !editingRecord"
                                #action
                            >
                                <Button
                                    type="button"
                                    class="shrink-0"
                                    :label="
                                        trans(
                                            'code_generation.actions.generate',
                                        )
                                    "
                                    icon="pi pi-sparkles"
                                    severity="secondary"
                                    outlined
                                    :loading="
                                        Boolean(generatingFields[field.name])
                                    "
                                    :disabled="
                                        Boolean(generatingFields[field.name])
                                    "
                                    :data-test="`generate-${field.name}`"
                                    @click="generateCode(field)"
                                />
                            </template>
                        </AdminCrudField>
                    </div>
                </div>

                <!-- Cancel and Save buttons -->
                <div class="flex justify-end gap-2 pt-2">
                    <!-- Cancel button -->
                    <Button
                        type="button"
                        :label="trans('actions.cancel')"
                        severity="secondary"
                        outlined
                        @click="dialogVisible = false"
                    />

                    <!-- Save button -->
                    <Button
                        type="submit"
                        :label="trans('actions.save')"
                        icon="pi pi-save"
                        :loading="submitting"
                        :disabled="submitting"
                    />
                </div>
            </form>
        </Dialog>
    </AdminLayout>
</template>
