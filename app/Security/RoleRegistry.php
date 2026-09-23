<?php

namespace App\Security;

use App\Enums\Permission;
use App\Enums\Role;

class RoleRegistry {
    public static function permissions(): array {
        return [
            Role::ADMIN->value => PermissionRegistry::names(),

            Role::SALES_VISITOR->value => [
                Permission::CUSTOMER_VIEW->value, Permission::CUSTOMER_CREATE->value, Permission::CUSTOMER_UPDATE->value,
                Permission::PRODUCT_VIEW->value, Permission::ORDER_VIEW->value, Permission::ORDER_CREATE->value,
                Permission::ORDER_UPDATE->value, Permission::ORDER_SUBMIT->value,
                Permission::ORDER_RETURN_VIEW->value, Permission::ORDER_RETURN_CREATE->value,
                Permission::ORDER_RETURN_UPDATE->value,
            ],

            Role::SCIENTIFIC_VISITOR->value => [
                Permission::DOCTOR_VIEW->value,
                Permission::VISIT_VIEW->value, Permission::VISIT_CREATE->value,
                Permission::VISIT_UPDATE->value, Permission::VISIT_COMPLETE->value,
                Permission::VISIT_CANCEL->value,
                Permission::SAMPLE_VIEW->value, Permission::SAMPLE_CREATE->value,
                Permission::SAMPLE_UPDATE->value,
            ],

            // Accountants can inspect the complete operational workspace and
            // perform accounting/operational edits, but final approvals remain
            // exclusively with the administrator.
            Role::ACCOUNTANT->value => array_values(array_unique([
                ...array_values(array_filter(
                    PermissionRegistry::names(),
                    static fn (string $permission): bool => str_ends_with($permission, '.view'),
                )),
                Permission::CUSTOMER_VIEW->value,
                Permission::ORDER_VIEW->value, Permission::ORDER_UPDATE->value, Permission::ORDER_CANCEL->value,
                Permission::ORDER_RETURN_VIEW->value, Permission::ORDER_RETURN_CREATE->value,
                Permission::ORDER_RETURN_UPDATE->value, Permission::ORDER_RETURN_CANCEL->value,
                Permission::INVOICE_VIEW->value, Permission::INVOICE_CREATE->value,
                Permission::INVOICE_UPDATE->value, Permission::INVOICE_CANCEL->value,
                Permission::PAYMENT_VIEW->value, Permission::PAYMENT_CREATE->value, Permission::PAYMENT_UPDATE->value,
                Permission::PAYMENT_CANCEL->value, Permission::PAYMENT_DELETE->value,
                Permission::DELIVERY_VIEW->value,
            ])),

            Role::SETTLEMENT_OPERATOR->value => [
                Permission::CUSTOMER_VIEW->value,
                Permission::ORDER_RETURN_VIEW->value,
                Permission::PAYMENT_VIEW->value, Permission::PAYMENT_CREATE->value,
                Permission::PAYMENT_CANCEL->value,
                Permission::DELIVERY_VIEW->value,
            ],

            Role::DELIVERY_OPERATOR->value => [
                Permission::ORDER_RETURN_VIEW->value, Permission::ORDER_RETURN_CREATE->value,
                Permission::ORDER_RETURN_UPDATE->value, Permission::ORDER_RETURN_SUBMIT->value,
                Permission::ORDER_RETURN_CANCEL->value,
                Permission::DELIVERY_VIEW->value, Permission::DELIVERY_CREATE->value, Permission::DELIVERY_UPDATE->value,
                Permission::DELIVERY_COMPLETE->value,
            ],
        ];
    }
}
