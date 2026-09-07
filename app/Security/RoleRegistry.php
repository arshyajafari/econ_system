<?php

    namespace App\Security;

    use App\Enums\Role;

    class RoleRegistry {
        public static function permissions(): array {
            return [
                Role::ADMIN->value => PermissionRegistry::names(),

                Role::SALES_VISITOR->value => [
                    'customers.view',
                    'customers.create',
                    'customers.update',

                    'products.view',

                    'orders.view',
                    'orders.create',
                    'orders.update',
                    'orders.submit',
                ],

                Role::SCIENTIFIC_VISITOR->value => [
                    'doctors.view',
                    'doctors.create',
                    'doctors.update',

                    'visits.view',
                    'visits.create',
                    'visits.update',

                    'products.view',
                ],

                Role::ACCOUNTANT->value => [
                    'customers.view',

                    'invoices.view',
                    'invoices.create',
                    'invoices.update',
                    'invoices.issue',
                    'invoices.cancel',

                    'payments.view',
                    'payments.create',
                    'payments.update',
                    'payments.confirm',
                    'payments.cancel',
                ],

                Role::SETTLEMENT_OPERATOR->value => [
                    'customers.view',

                    'payments.view',
                    'payments.create',
                    'payments.update',
                    'payments.confirm',
                    'payments.cancel',
                ],

                Role::DELIVERY_OPERATOR->value => [
                    'orders.view',
                    'deliveries.view',
                    'deliveries.create',
                    'deliveries.update',
                    'deliveries.prepare',
                    'deliveries.ship',
                    'deliveries.complete',
                    'deliveries.cancel',
                ],
            ];
        }
    }
