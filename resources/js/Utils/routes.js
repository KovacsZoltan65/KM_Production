export const routes = {
    dashboard: '/dashboard',
    login: '/login',
    'login.store': '/login',
    'password.request': '/forgot-password',
    'password.email': '/forgot-password',
    'password.store': '/reset-password',
    'verification.send': '/email/verification-notification',
    'profile.edit': '/profile',
    'profile.update': '/profile',
    'profile.password': '/password',
    logout: '/logout',
    'admin.dashboard': '/admin/dashboard',
    'admin.reports.customer-orders': '/admin/reports/customer-orders',
    'admin.reports.production': '/admin/reports/production',
    'admin.reports.inventory': '/admin/reports/inventory',
    'admin.reports.procurement': '/admin/reports/procurement',
    'admin.reports.quality': '/admin/reports/quality',
    'admin.reports.shop-floor': '/admin/reports/shop-floor',
    'admin.users.index': '/admin/users',
    'admin.users.store': '/admin/users',
    'admin.users.update': '/admin/users/{user}',
    'admin.users.destroy': '/admin/users/{user}',
    'admin.roles.index': '/admin/roles',
    'admin.roles.store': '/admin/roles',
    'admin.roles.update': '/admin/roles/{role}',
    'admin.roles.destroy': '/admin/roles/{role}',
    'admin.permissions.index': '/admin/permissions',
    'admin.employees.index': '/admin/employees',
    'admin.employees.store': '/admin/employees',
    'admin.employees.update': '/admin/employees/{employee}',
    'admin.employees.destroy': '/admin/employees/{employee}',
    'admin.factory-units.index': '/admin/factory-units',
    'admin.factory-units.store': '/admin/factory-units',
    'admin.factory-units.update': '/admin/factory-units/{factoryUnit}',
    'admin.factory-units.destroy': '/admin/factory-units/{factoryUnit}',
    'admin.locations.index': '/admin/locations',
    'admin.locations.store': '/admin/locations',
    'admin.locations.update': '/admin/locations/{location}',
    'admin.locations.destroy': '/admin/locations/{location}',
    'admin.professional-roles.index': '/admin/professional-roles',
    'admin.professional-roles.store': '/admin/professional-roles',
    'admin.professional-roles.update': '/admin/professional-roles/{professionalRole}',
    'admin.professional-roles.destroy': '/admin/professional-roles/{professionalRole}',
    'admin.items.index': '/admin/items',
    'admin.items.store': '/admin/items',
    'admin.items.update': '/admin/items/{item}',
    'admin.items.destroy': '/admin/items/{item}',
    'admin.boms.index': '/admin/boms',
    'admin.boms.store': '/admin/boms',
    'admin.boms.update': '/admin/boms/{bom}',
    'admin.boms.destroy': '/admin/boms/{bom}',
    'admin.operation-types.index': '/admin/operation-types',
    'admin.operation-types.store': '/admin/operation-types',
    'admin.operation-types.update': '/admin/operation-types/{operationType}',
    'admin.operation-types.destroy': '/admin/operation-types/{operationType}',
    'admin.operation-sequences.index': '/admin/operation-sequences',
    'admin.operation-sequences.store': '/admin/operation-sequences',
    'admin.operation-sequences.update': '/admin/operation-sequences/{operationSequence}',
    'admin.operation-sequences.destroy': '/admin/operation-sequences/{operationSequence}',
    'admin.customers.index': '/admin/customers',
    'admin.customers.store': '/admin/customers',
    'admin.customers.update': '/admin/customers/{customer}',
    'admin.customers.destroy': '/admin/customers/{customer}',
    'admin.suppliers.index': '/admin/suppliers',
    'admin.suppliers.store': '/admin/suppliers',
    'admin.suppliers.update': '/admin/suppliers/{supplier}',
    'admin.suppliers.destroy': '/admin/suppliers/{supplier}',
    'admin.customer-orders.index': '/admin/customer-orders',
    'admin.customer-orders.show': '/admin/customer-orders/{customerOrder}',
    'admin.customer-orders.store': '/admin/customer-orders',
    'admin.customer-orders.update': '/admin/customer-orders/{customerOrder}',
    'admin.customer-orders.destroy': '/admin/customer-orders/{customerOrder}',
    'admin.customer-orders.confirm': '/admin/customer-orders/{customerOrder}/confirm',
    'admin.customer-orders.cancel': '/admin/customer-orders/{customerOrder}/cancel',
    'admin.production-plans.index': '/admin/production-plans',
    'admin.production-plans.show': '/admin/production-plans/{productionPlan}',
    'admin.production-plans.store': '/admin/production-plans',
    'admin.production-plans.update': '/admin/production-plans/{productionPlan}',
    'admin.production-plans.destroy': '/admin/production-plans/{productionPlan}',
    'admin.production-plans.approve': '/admin/production-plans/{productionPlan}/approve',
    'admin.production-plans.generate-production-orders': '/admin/production-plans/{productionPlan}/generate-production-orders',
    'admin.production-tasks.index': '/admin/production-tasks',
    'admin.production-tasks.show': '/admin/production-tasks/{productionTask}',
    'admin.production-tasks.store': '/admin/production-tasks',
    'admin.production-tasks.update': '/admin/production-tasks/{productionTask}',
    'admin.production-tasks.destroy': '/admin/production-tasks/{productionTask}',
    'admin.production-tasks.generate-from-order': '/admin/production-tasks/generate-from-order',
    'admin.production-tasks.start': '/admin/production-tasks/{productionTask}/start',
    'admin.production-tasks.finish': '/admin/production-tasks/{productionTask}/finish',
    'admin.production-tasks.materials.store': '/admin/production-tasks/{productionTask}/materials',
    'admin.production-tasks.quality-checks.store': '/admin/production-tasks/{productionTask}/quality-checks',
    'admin.shop-floor.index': '/admin/shop-floor',
    'admin.shop-floor.my-tasks': '/admin/shop-floor/my-tasks',
    'admin.inventory.stock-balances.index': '/admin/inventory/stock-balances',
    'admin.inventory.stock-movements.index': '/admin/inventory/stock-movements',
    'admin.inventory.stock-reservations.index': '/admin/inventory/stock-reservations',
    'admin.inventory.stock-reservations.release': '/admin/inventory/stock-reservations/{stockReservation}/release',
    'admin.inventory.material-requirements.index': '/admin/inventory/material-requirements',
    'admin.inventory.shortages.index': '/admin/inventory/shortages',
    'admin.procurement.dashboard': '/admin/procurement/dashboard',
    'admin.purchase-requisitions.index': '/admin/purchase-requisitions',
    'admin.purchase-requisitions.show': '/admin/purchase-requisitions/{purchaseRequisition}',
    'admin.purchase-requisitions.store': '/admin/purchase-requisitions',
    'admin.purchase-requisitions.update': '/admin/purchase-requisitions/{purchaseRequisition}',
    'admin.purchase-requisitions.destroy': '/admin/purchase-requisitions/{purchaseRequisition}',
    'admin.purchase-requisitions.approve': '/admin/purchase-requisitions/{purchaseRequisition}/approve',
    'admin.purchase-requisitions.generate-from-material-requirements': '/admin/purchase-requisitions/generate-from-material-requirements',
    'admin.purchase-requisitions.generate-purchase-order': '/admin/purchase-requisitions/{purchaseRequisition}/generate-purchase-order',
    'admin.purchase-orders.index': '/admin/purchase-orders',
    'admin.purchase-orders.show': '/admin/purchase-orders/{purchaseOrder}',
    'admin.purchase-orders.store': '/admin/purchase-orders',
    'admin.purchase-orders.update': '/admin/purchase-orders/{purchaseOrder}',
    'admin.purchase-orders.destroy': '/admin/purchase-orders/{purchaseOrder}',
    'admin.purchase-orders.approve': '/admin/purchase-orders/{purchaseOrder}/approve',
    'admin.purchase-orders.close': '/admin/purchase-orders/{purchaseOrder}/close',
    'admin.goods-receipts.index': '/admin/goods-receipts',
    'admin.goods-receipts.show': '/admin/goods-receipts/{goodsReceipt}',
    'admin.goods-receipts.store': '/admin/goods-receipts',
    'admin.goods-receipts.post': '/admin/goods-receipts/{goodsReceipt}/post',
    'admin.documents.index': '/admin/documents',
    'admin.documents.show': '/admin/documents/{document}',
    'admin.documents.store': '/admin/documents',
    'admin.documents.update': '/admin/documents/{document}',
    'admin.documents.destroy': '/admin/documents/{document}',
    'admin.documents.download': '/admin/documents/{document}/download',
    'admin.documents.approve': '/admin/documents/{document}/approve',
    'admin.documents.make-current': '/admin/documents/{document}/make-current',
};

const valueFrom = (value) => {
    if (value && typeof value === 'object') {
        return value.id;
    }

    return value;
};

export const route = (name, parameters = {}) => {
    const template = routes[name];

    if (!template) {
        throw new Error(`Unknown route name: ${name}`);
    }

    const values = Array.isArray(parameters)
        ? [...parameters]
        : parameters && typeof parameters === 'object'
            ? { ...parameters }
            : [parameters];

    return template.replace(/{([^}]+)}/g, (match, key) => {
        const value = Array.isArray(values) ? values.shift() : values[key];

        if (value === undefined || value === null) {
            throw new Error(`Missing route parameter [${key}] for route [${name}]`);
        }

        return encodeURIComponent(valueFrom(value));
    });
};
