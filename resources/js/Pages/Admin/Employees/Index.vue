<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
    options: Object,
});

const columns = [
    { field: 'employee_number', headerKey: 'fields.employee_number' },
    { field: 'name', headerKey: 'fields.name' },
    { field: 'email', headerKey: 'fields.email' },
    { field: 'professionalRole.name', headerKey: 'fields.professional_role', sortable: false, format: (record) => record.professional_role?.name || '-' },
    { field: 'is_active', headerKey: 'fields.status', type: 'status' },
];

const fields = [
    { name: 'employee_number', labelKey: 'fields.employee_number', type: 'text' },
    { name: 'name', labelKey: 'fields.name', type: 'text' },
    { name: 'email', labelKey: 'fields.email', type: 'email' },
    { name: 'phone', labelKey: 'fields.phone', type: 'text' },
    { name: 'professional_role_id', labelKey: 'fields.professional_role', type: 'select', options: 'professionalRoles', optionLabel: 'name', optionValue: 'id' },
    { name: 'user_id', labelKey: 'fields.user', type: 'select', options: 'users', optionLabel: 'email', optionValue: 'id' },
    { name: 'is_active', labelKey: 'status.active', type: 'checkbox', default: true },
    { name: 'hired_at', labelKey: 'fields.hired_at', type: 'date' },
    { name: 'left_at', labelKey: 'fields.left_at', type: 'date' },
];
</script>

<template>
    <AdminCrudPage
        title=""
        title-key="master_data.employees.title"
        subtitle-key="master_data.employees.subtitle"
        route-name="admin.employees"
        create-label-key="master_data.employees.create"
        :records="records"
        :filters="filters"
        :options="options"
        :columns="columns"
        :fields="fields"
    />
</template>
