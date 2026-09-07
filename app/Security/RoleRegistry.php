<?php

    namespace App\Security;

    use App\Enums\Permission;
    use App\Enums\Role;

    class RoleRegistry {
        public static function permissions(): array {
            return [
                Role::ADMIN->value => PermissionRegistry::names(),

                Role::SALES_VISITOR->value => [
                    Permission::CUSTOMER_VIEW->value,
                    Permission::CUSTOMER_CREATE->value,
                    Permission::CUSTOMER_UPDATE->value,

                    Permission::PRODUCT_VIEW->value,

                    Permission::ORDER_VIEW->value,
                    Permission::ORDER_CREATE->value,
                    Permission::ORDER_UPDATE->value,
                    Permission::ORDER_SUBMIT->value,
                ],

                Role::SCIENTIFIC_VISITOR->value => [
                    Permission::DOCTOR_VIEW->value,
                    Permission::DOCTOR_CREATE->value,
                    Permission::DOCTOR_UPDATE->value,

                    Permission::VISIT_VIEW->value,
                    Permission::VISIT_CREATE->value,
                    Permission::VISIT_UPDATE->value,

                    Permission::PRODUCT_VIEW->value,
                ],

                Role::ACCOUNTANT->value => [
                    Permission::CUSTOMER_VIEW->value,

                    Permission::INVOICE_VIEW->value,
                    Permission::INVOICE_CREATE->value,
                    Permission::INVOICE_UPDATE->value,
                    Permission::INVOICE_ISSUE->value,
                    Permission::INVOICE_CANCEL->value,

                    Permission::PAYMENT_VIEW->value,
                    Permission::PAYMENT_CREATE->value,
                    Permission::PAYMENT_UPDATE->value,
                    Permission::PAYMENT_CONFIRM->value,
                    Permission::PAYMENT_CANCEL->value,
                ],

                Role::SETTLEMENT_OPERATOR->value => [
                    Permission::CUSTOMER_VIEW->value,

                    Permission::PAYMENT_VIEW->value,
                    Permission::PAYMENT_CREATE->value,
                    Permission::PAYMENT_UPDATE->value,
                    Permission::PAYMENT_CONFIRM->value,
                    Permission::PAYMENT_CANCEL->value,
                ],

                Role::DELIVERY_OPERATOR->value => [
                    Permission::ORDER_VIEW->value,

                    Permission::DELIVERY_VIEW->value,
                    Permission::DELIVERY_CREATE->value,
                    Permission::DELIVERY_UPDATE->value,
                    Permission::DELIVERY_PREPARE->value,
                    Permission::DELIVERY_SHIP->value,
                    Permission::DELIVERY_COMPLETE->value,
                    Permission::DELIVERY_CANCEL->value,
                ],
            ];
        }
    }
