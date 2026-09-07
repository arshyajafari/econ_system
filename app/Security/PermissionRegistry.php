<?php

namespace App\Security;

class PermissionRegistry
{
    public static function all(): array
    {
        return [
            'customers' => ['view', 'create', 'update', 'delete', 'restore', 'change_status', 'export'],
            'employees' => ['view', 'create', 'update', 'delete', 'export'],
            'doctors' => ['view', 'create', 'update', 'delete', 'restore', 'change_status', 'export'],
            'brands' => ['view', 'create', 'update', 'delete', 'change_activity'],
            'product_categories' => ['view', 'create', 'update', 'delete', 'change_activity'],
            'products' => ['view', 'create', 'update', 'delete', 'change_status'],
            'inventory_batches' => ['view', 'create', 'update', 'delete'],
            'inventory_adjustments' => ['view', 'create'],
            'inventory_movements' => ['view'],
            'orders' => ['view', 'create', 'update', 'submit', 'confirm', 'complete', 'cancel', 'export'],
            'order_returns' => ['view', 'create', 'update', 'submit', 'confirm', 'complete', 'cancel', 'allocate', 'export'],
            'invoices' => ['view', 'create', 'update', 'issue', 'cancel'],
            'payments' => ['view', 'create', 'update', 'delete', 'confirm', 'cancel'],
            'deliveries' => ['view', 'create', 'update', 'prepare', 'ship', 'complete', 'cancel', 'export'],
            'visits' => ['view', 'create', 'update', 'delete', 'complete', 'cancel', 'export'],
            'samples' => ['view', 'create', 'update', 'delete', 'export'],
        ];
    }

    public static function names(): array
    {
        $permissions = [];

        foreach (self::all() as $resource => $abilities) {
            foreach ($abilities as $ability) {
                $permissions[] = "{$resource}.{$ability}";
            }
        }

        return $permissions;
    }
}
