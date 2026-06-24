<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/Utils/routes';
import { Head, Link } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';

defineProps({ metrics: Object });

const number = (value) => Number(value || 0).toFixed(3);
</script>

<template>
    <Head title="Procurement Dashboard" />

    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-2xl font-semibold">Procurement Dashboard</h1>
                <p class="mt-1 text-sm text-slate-600">Demand, purchasing, receipt, and inbound stock flow.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <Link :href="route('admin.purchase-requisitions.index')" class="rounded border border-slate-200 bg-white p-4 hover:border-blue-300">
                    <div class="text-xs font-medium uppercase text-slate-500">Open Requisitions</div>
                    <div class="mt-2 text-3xl font-semibold">{{ metrics.open_requisitions }}</div>
                </Link>
                <Link :href="route('admin.purchase-orders.index')" class="rounded border border-slate-200 bg-white p-4 hover:border-blue-300">
                    <div class="text-xs font-medium uppercase text-slate-500">Open Purchase Orders</div>
                    <div class="mt-2 text-3xl font-semibold">{{ metrics.open_purchase_orders }}</div>
                </Link>
                <Link :href="route('admin.goods-receipts.index')" class="rounded border border-slate-200 bg-white p-4 hover:border-blue-300">
                    <div class="text-xs font-medium uppercase text-slate-500">Pending Goods Receipts</div>
                    <div class="mt-2 text-3xl font-semibold">{{ metrics.pending_goods_receipts }}</div>
                </Link>
                <Link :href="route('admin.inventory.shortages.index')" class="rounded border border-slate-200 bg-white p-4 hover:border-blue-300">
                    <div class="text-xs font-medium uppercase text-slate-500">Shortages Count</div>
                    <div class="mt-2 text-3xl font-semibold">{{ metrics.shortages_count }}</div>
                </Link>
            </div>

            <div class="rounded border border-slate-200 bg-white p-4">
                <h2 class="mb-3 text-lg font-semibold">Top Missing Materials</h2>
                <DataTable :value="metrics.top_missing_materials" data-key="item_id">
                    <Column header="Item">
                        <template #body="{ data }">{{ data.item_number }} - {{ data.name }}</template>
                    </Column>
                    <Column header="Missing">
                        <template #body="{ data }">{{ number(data.missing_quantity) }} {{ data.unit }}</template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </AdminLayout>
</template>
