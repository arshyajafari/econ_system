<?php

    namespace App\Enums;

    enum Permission: string {
        case DASHBOARD_VIEW = 'dashboard.view';

        // Customers
        case CUSTOMER_VIEW = 'customers.view';
        case CUSTOMER_CREATE = 'customers.create';
        case CUSTOMER_UPDATE = 'customers.update';
        case CUSTOMER_DELETE = 'customers.delete';
        case CUSTOMER_RESTORE = 'customers.restore';
        case CUSTOMER_CHANGE_STATUS = 'customers.change_status';
        case CUSTOMER_EXPORT = 'customers.export';

        // Employees
        case EMPLOYEE_VIEW = 'employees.view';
        case EMPLOYEE_CREATE = 'employees.create';
        case EMPLOYEE_UPDATE = 'employees.update';
        case EMPLOYEE_DELETE = 'employees.delete';
        case EMPLOYEE_EXPORT = 'employees.export';

        // Doctors
        case DOCTOR_VIEW = 'doctors.view';
        case DOCTOR_CREATE = 'doctors.create';
        case DOCTOR_UPDATE = 'doctors.update';
        case DOCTOR_DELETE = 'doctors.delete';
        case DOCTOR_RESTORE = 'doctors.restore';
        case DOCTOR_CHANGE_STATUS = 'doctors.change_status';
        case DOCTOR_EXPORT = 'doctors.export';

        // Brands
        case BRAND_VIEW = 'brands.view';
        case BRAND_CREATE = 'brands.create';
        case BRAND_UPDATE = 'brands.update';
        case BRAND_DELETE = 'brands.delete';
        case BRAND_CHANGE_ACTIVITY = 'brands.change_activity';

        // Product Categories
        case PRODUCT_CATEGORY_VIEW = 'product_categories.view';
        case PRODUCT_CATEGORY_CREATE = 'product_categories.create';
        case PRODUCT_CATEGORY_UPDATE = 'product_categories.update';
        case PRODUCT_CATEGORY_DELETE = 'product_categories.delete';
        case PRODUCT_CATEGORY_CHANGE_ACTIVITY = 'product_categories.change_activity';

        // Products
        case PRODUCT_VIEW = 'products.view';
        case PRODUCT_CREATE = 'products.create';
        case PRODUCT_UPDATE = 'products.update';
        case PRODUCT_DELETE = 'products.delete';
        case PRODUCT_CHANGE_STATUS = 'products.change_status';

        // Inventory Batches
        case INVENTORY_BATCH_VIEW = 'inventory_batches.view';
        case INVENTORY_BATCH_CREATE = 'inventory_batches.create';
        case INVENTORY_BATCH_UPDATE = 'inventory_batches.update';
        case INVENTORY_BATCH_DELETE = 'inventory_batches.delete';

        // Inventory Adjustments
        case INVENTORY_ADJUSTMENT_VIEW = 'inventory_adjustments.view';
        case INVENTORY_ADJUSTMENT_CREATE = 'inventory_adjustments.create';

        // Inventory Movements
        case INVENTORY_MOVEMENT_VIEW = 'inventory_movements.view';

        // Orders
        case ORDER_VIEW = 'orders.view';
        case ORDER_CREATE = 'orders.create';
        case ORDER_UPDATE = 'orders.update';
        case ORDER_SUBMIT = 'orders.submit';
        case ORDER_CONFIRM = 'orders.confirm';
        case ORDER_COMPLETE = 'orders.complete';
        case ORDER_CANCEL = 'orders.cancel';
        case ORDER_EXPORT = 'orders.export';

        // Order Returns
        case ORDER_RETURN_VIEW = 'order_returns.view';
        case ORDER_RETURN_CREATE = 'order_returns.create';
        case ORDER_RETURN_UPDATE = 'order_returns.update';
        case ORDER_RETURN_SUBMIT = 'order_returns.submit';
        case ORDER_RETURN_CONFIRM = 'order_returns.confirm';
        case ORDER_RETURN_COMPLETE = 'order_returns.complete';
        case ORDER_RETURN_CANCEL = 'order_returns.cancel';
        case ORDER_RETURN_ALLOCATE = 'order_returns.allocate';
        case ORDER_RETURN_EXPORT = 'order_returns.export';

        // Invoices
        case INVOICE_VIEW = 'invoices.view';
        case INVOICE_CREATE = 'invoices.create';
        case INVOICE_UPDATE = 'invoices.update';
        case INVOICE_ISSUE = 'invoices.issue';
        case INVOICE_CANCEL = 'invoices.cancel';

        // Payments
        case PAYMENT_VIEW = 'payments.view';
        case PAYMENT_CREATE = 'payments.create';
        case PAYMENT_UPDATE = 'payments.update';
        case PAYMENT_DELETE = 'payments.delete';
        case PAYMENT_CONFIRM = 'payments.confirm';
        case PAYMENT_CANCEL = 'payments.cancel';

        // Deliveries
        case DELIVERY_VIEW = 'deliveries.view';
        case DELIVERY_CREATE = 'deliveries.create';
        case DELIVERY_UPDATE = 'deliveries.update';
        case DELIVERY_PREPARE = 'deliveries.prepare';
        case DELIVERY_SHIP = 'deliveries.ship';
        case DELIVERY_COMPLETE = 'deliveries.complete';
        case DELIVERY_CANCEL = 'deliveries.cancel';
        case DELIVERY_EXPORT = 'deliveries.export';

        // Visits
        case VISIT_VIEW = 'visits.view';
        case VISIT_CREATE = 'visits.create';
        case VISIT_UPDATE = 'visits.update';
        case VISIT_DELETE = 'visits.delete';
        case VISIT_COMPLETE = 'visits.complete';
        case VISIT_CANCEL = 'visits.cancel';
        case VISIT_EXPORT = 'visits.export';

        // Samples
        case SAMPLE_VIEW = 'samples.view';
        case SAMPLE_CREATE = 'samples.create';
        case SAMPLE_UPDATE = 'samples.update';
        case SAMPLE_DELETE = 'samples.delete';
        case SAMPLE_EXPORT = 'samples.export';
    }
