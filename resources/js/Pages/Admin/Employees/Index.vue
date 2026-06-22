<script setup>
import AdminCrudPage from '@/Components/Admin/AdminCrudPage.vue';

defineProps({
    records: Object,
    filters: Object,
    options: Object,
});

const columns = [
    { field: 'employee_number', header: 'Employee no.' },
    { field: 'name', header: 'Name' },
    { field: 'email', header: 'Email' },
    { field: 'professionalRole.name', header: 'Professional role', sortable: false, format: (record) => record.professional_role?.name || '-' },
    { field: 'is_active', header: 'Status', type: 'status' },
];

const fields = [
    { name: 'employee_number', label: 'Employee number', type: 'text' },
    { name: 'name', label: 'Name', type: 'text' },
    { name: 'email', label: 'Email', type: 'email' },
    { name: 'phone', label: 'Phone', type: 'text' },
    { name: 'professional_role_id', label: 'Professional role', type: 'select', options: 'professionalRoles', optionLabel: 'name', optionValue: 'id' },
    { name: 'user_id', label: 'User', type: 'select', options: 'users', optionLabel: 'email', optionValue: 'id' },
    { name: 'is_active', label: 'Active', type: 'checkbox', default: true },
    { name: 'hired_at', label: 'Hired at', type: 'date' },
    { name: 'left_at', label: 'Left at', type: 'date' },
];
</script>

<template>
    <AdminCrudPage
        title="Employees"
        subtitle="Manage employee master data and optional user links."
        base-url="/admin/employees"
        create-label="Create employee"
        :records="records"
        :filters="filters"
        :options="options"
        :columns="columns"
        :fields="fields"
    />
</template>
