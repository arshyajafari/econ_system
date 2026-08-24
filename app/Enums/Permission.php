<?php

    namespace app\Enums;

    enum Permission: string {
        // user
        case USER_VIEW = 'users.view';
        case USER_CREATE = 'users.create';
        case USER_UPDATE = 'users.update';
        case USER_DELETE = 'users.delete';
        case USER_RESTORE = 'users.restore';
        case USER_ASSIGN_ROLE = 'users.assign_roles';
        case USER_ASSIGN_PERMISSION = 'users.assign_permissions';
        case USER_RESET_PASSWORD = 'users.reset_password';
        case USER_ACTIVATE = 'users.activate';
        case USER_DEACTIVATE = 'users.deactivate';
        case USER_EXPORT = 'users.export';

        // role
        case ROLE_VIEW = 'roles.view';
        case ROLE_CREATE = 'roles.create';
        case ROLE_UPDATE = 'roles.update';
        case ROLE_DELETE = 'roles.delete';
        case ROLE_ASSIGN_PERMISSION = 'roles.assign_permissions';

        // permission
        case PERMISSION_VIEW = 'permissions.view';
        case PERMISSION_SYNC = 'permissions.sync';

        // customer
        case CUSTOMER_VIEW = 'customer.view';
        case CUSTOMER_CREATE = 'customer.create';
        case CUSTOMER_UPDATE = 'customer.update';
        case CUSTOMER_DELETE = 'customer.delete';
        case CUSTOMER_RESTORE = 'customer.restore';
        case CUSTOMER_EXPORT = 'customer.export';
        case CUSTOMER_STATEMENT = 'customer.statement';
        case CUSTOMER_ACCOUNT = 'customer.account';
        case CUSTOMER_CHANGE_PRICE_GROUP = 'customer.change_price_group';
        case CUSTOMER_CHANGE_CREDIT = 'customer.change_credit';
        case CUSTOMER_LOCATION = 'customer.location';

        // settlement
        case SETTLEMENT_VIEW = 'settlements.view';
        case SETTLEMENT_CREATE = 'settlements.create';
        case SETTLEMENT_UPDATE = 'settlements.update';
        case SETTLEMENT_DELETE = 'settlements.delete';
        case SETTLEMENT_CONFIRM = 'settlements.confirm';
        case SETTLEMENT_EXPORT = 'settlements.export';

        // category
        case CATEGORY_VIEW = 'categories.view';
        case CATEGORY_CREATE = 'categories.create';
        case CATEGORY_UPDATE = 'categories.update';
        case CATEGORY_DELETE = 'categories.delete';

        // product
        case PRODUCT_VIEW = 'products.view';
        case PRODUCT_CREATE = 'products.create';
        case PRODUCT_UPDATE = 'products.update';
        case PRODUCT_DELETE = 'products.delete';
        case PRODUCT_RESTORE = 'products.restore';
        case PRODUCT_EXPORT = 'products.export';
        case PRODUCT_CHANGE_PRICE = 'products.change_price';
        case PRODUCT_CHANGE_STATUS = 'products.change_status';

        // order
        case ORDER_VIEW = 'order.view';
        case ORDER_CREATE = 'order.create';
        case ORDER_UPDATE = 'order.update';
        case ORDER_DELETE = 'order.delete';
        case ORDER_CONFIRM = 'order.confirm';
        case ORDER_CANCEL = 'order.cancel';
        case ORDER_EXPORT = 'order.export';
        case ORDER_PRINT = 'order.print';
        case ORDER_CHANGE_PRICE = 'order.change_price';
        case ORDER_CHANGE_DISCOUNT = 'order.change_discount';
        case ORDER_CHANGE_QUANTITY = 'order.change_quantity';

        // payment
        case PAYMENT_VIEW = 'payments.view';
        case PAYMENT_CREATE = 'payments.create';
        case PAYMENT_UPDATE = 'payments.update';
        case PAYMENT_DELETE = 'payments.delete';
        case PAYMENT_CONFIRM = 'payments.confirm';
        case PAYMENT_EXPORT = 'payments.export';
        case PAYMENT_PRINT = 'payments.print';

        // physician
        case PHYSICIAN_VIEW = 'physicians.view';
        case PHYSICIAN_CREATE = 'physicians.create';
        case PHYSICIAN_UPDATE = 'physicians.update';
        case PHYSICIAN_DELETE = 'physicians.delete';
        case PHYSICIAN_EXPORT = 'physicians.export';

        // visits
        case VISIT_VIEW = 'visits.view';
        case VISIT_CREATE = 'visits.create';
        case VISIT_UPDATE = 'visits.update';
        case VISIT_DELETE = 'visits.delete';
        case VISIT_CONFIRM = 'visits.confirm';
        case VISIT_EXPORT = 'visits.export';

        // sample
        case SAMPLE_VIEW = 'samples.view';
        case SAMPLE_CREATE = 'samples.create';
        case SAMPLE_UPDATE = 'samples.update';
        case SAMPLE_DELETE = 'samples.delete';
        case SAMPLE_EXPORT = 'samples.export';

        // gift
        case GIFT_VIEW = 'gifts.view';
        case GIFT_CREATE = 'gifts.create';
        case GIFT_UPDATE = 'gifts.update';
        case GIFT_DELETE = 'gifts.delete';

        // delivery
        case DELIVERY_VIEW = 'delivery.view';
        case DELIVERY_CREATE = 'delivery.create';
        case DELIVERY_UPDATE = 'delivery.update';
        case DELIVERY_DELETE = 'delivery.delete';
        case DELIVERY_CONFIRM = 'delivery.confirm';
        case DELIVERY_EXPORT = 'delivery.export';
        case DELIVERY_PRINT = 'delivery.print';

        // return
        case RETURN_VIEW = 'returns.view';
        case RETURN_CREATE = 'returns.create';
        case RETURN_UPDATE = 'returns.update';
        case RETURN_DELETE = 'returns.delete';
        case RETURN_CONFIRM = 'returns.confirm';
        case RETURN_EXPORT = 'returns.export';

        // invoice
        case INVOICE_VIEW = 'invoices.view';
        case INVOICE_EXPORT = 'invoices.export';
        case INVOICE_PRINT = 'invoices.print';

        // warhouse
        case WARHOUSE_VIEW = 'warehouses.view';
        case WARHOUSE_STOCK = 'warehouses.stock';
        case WARHOUSE_ADJUSTMENT = 'warehouses.adjustment';
        case WARHOUSE_TRANSFER = 'warehouses.transfer';

        // dashboard
        case DASHBOARD_VIEW = 'dashboards.view';
        case DASHBOARD_SALE = 'dashboards.sales';
        case DASHBOARD_VISIT = 'dashboards.visits';
        case DASHBOARD_PAYMENT = 'dashboards.payments';
        case DASHBOARD_STATISTICS = 'dashboards.statistics';

        // report
        case REPORT_VIEW = 'reports.view';
        case REPORT_SALE = 'reports.sales';
        case REPORT_VISIT = 'reports.visits';
        case REPORT_CUSTOMER = 'reports.customers';
        case REPORT_PAYMENT = 'reports.payments';
        case REPORT_PRODUCT = 'reports.products';
        case REPORT_EXPORT = 'reports.export';

        // message
        case MESSAGE_VIEW = 'messages.view';
        case MESSAGE_CREATE = 'messages.create';
        case MESSAGE_UPDATE = 'messages.update';
        case MESSAGE_DELETE = 'messages.delete';
        case MESSAGE_SEND = 'messages.send';

        // notification
        case NOTIFICATION_VIEW = 'notifications.view';
        case NOTIFICATION_SEND = 'notifications.send';

        // settings
        case SETTING_VIEW = 'settings.view';
        case SETTING_UPDATE = 'settings.update';

        // activity
        case ACTIVITY_VIEW = 'activity.view';
        case ACTIVITY_EXPORT = 'activity.export';

        // backup
        case BACKUP_CREATE = 'backup.create';
        case BACKUP_RESTORE = 'backup.restore';
        case BACKUP_DOWNLOAD = 'backup.download';

        // notification
        case system_CACHE_CLEAR = 'system.cache_clear';
        case system_QUEUE_RESTART = 'system.queue_restart';
        case system_OPTIMIZE = 'system.optimize';
    }
