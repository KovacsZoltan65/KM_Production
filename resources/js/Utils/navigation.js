/**
 * A navigációs címkékhez tartozó backend jogosultságok.
 * A csoportcímek szándékosan nem szerepelnek a leképezésben.
 */
export const NAVIGATION_PERMISSIONS = Object.freeze({
    "admin.dashboard.title": "dashboard.view",
    "navigation.users": "users.view",
    "navigation.roles": "roles.view",
    "navigation.permissions": "permissions.view",
    "navigation.employees": "employees.view",
    "navigation.factory_units": "factory-units.view",
    "navigation.locations": "locations.view",
    "navigation.professional_roles": "professional-roles.view",
    "navigation.items": "items.view",
    "navigation.boms": "boms.view",
    "navigation.operation_types": "operation-types.view",
    "navigation.operation_sequences": "operation-sequences.view",
    "navigation.customers": "customers.view",
    "navigation.suppliers": "suppliers.view",
    "navigation.customer_orders": "customer-orders.view",
    "navigation.production_plans": "production-plans.view",
    "navigation.capacity_dashboard": "capacity.view",
    "navigation.factory_capacity": "capacity.view",
    "navigation.employee_capacity": "capacity.view",
    "navigation.capacity_schedule": "capacity.view",
    "navigation.capacity_simulation": "capacity.view",
    "navigation.shop_floor": "shop-floor.view",
    "navigation.my_tasks": "shop-floor.view",
    "navigation.production_tasks": "production-tasks.view",
    "navigation.inventory": "inventory.view",
    "navigation.stock_balances": "inventory.view",
    "navigation.stock_movements": "inventory.view",
    "navigation.reservations": "inventory.view",
    "navigation.material_requirements": "inventory.view",
    "navigation.shortages": "inventory.view",
    "navigation.procurement_dashboard": "procurement.view",
    "navigation.purchase_requisitions": "procurement.view",
    "navigation.purchase_orders": "procurement.view",
    "navigation.goods_receipts": "procurement.view",
    "navigation.document_library": "documents.view",
    "navigation.customer_orders_report": "reports.view",
    "navigation.production_report": "reports.view",
    "navigation.inventory_report": "reports.view",
    "navigation.procurement_report": "reports.view",
    "navigation.quality_report": "reports.view",
    "navigation.shop_floor_report": "reports.view",
    "navigation.mi_dashboard": "intelligence.view",
    "navigation.bottlenecks": "intelligence.view",
    "navigation.material_forecast": "intelligence.view",
    "navigation.supplier_performance": "intelligence.view",
    "navigation.quality_trends": "intelligence.view",
    "navigation.production_risks": "intelligence.view",
    "navigation.recommendations": "intelligence.view",
});

export const normalizeNavigationPath = (url) => {
    const path = String(url || "/").split(/[?#]/)[0];
    return path.length > 1 ? path.replace(/\/+$/, "") : path;
};

export const canAccessNavigationItem = (
    item,
    permissions = [],
    roles = [],
) => {
    const permission = NAVIGATION_PERMISSIONS[item.labelKey];

    return (
        !permission ||
        roles.includes("super-admin") ||
        permissions.includes(permission)
    );
};

/**
 * A lapos menü csoportcímeit csak akkor tartja meg, ha van látható gyermekük.
 */
export const filterNavigationItems = (items, permissions = [], roles = []) => {
    const visible = [];
    let pendingGroup = null;

    for (const item of items) {
        if (item.disabled) {
            pendingGroup = item;
            continue;
        }

        if (!canAccessNavigationItem(item, permissions, roles)) {
            continue;
        }

        if (pendingGroup) {
            visible.push(pendingGroup);
            pendingGroup = null;
        }

        visible.push(item);
    }

    return visible;
};

export const findActiveNavigationHref = (items, currentUrl) => {
    const currentPath = normalizeNavigationPath(currentUrl);

    return items
        .filter((item) => !item.disabled && item.href)
        .map((item) => normalizeNavigationPath(item.href))
        .filter(
            (href) =>
                currentPath === href || currentPath.startsWith(`${href}/`),
        )
        .sort((first, second) => second.length - first.length)[0];
};
