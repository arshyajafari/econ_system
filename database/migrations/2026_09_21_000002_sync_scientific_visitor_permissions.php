<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'scientific visitor',
            'guard_name' => 'web',
        ]);

        $permissions = [
            'doctors.view',
            'products.view',
            'orders.view',
            'orders.create',
            'orders.update',
            'orders.submit',
            'visits.view',
            'visits.create',
            'visits.update',
            'visits.complete',
            'visits.cancel',
            'samples.view',
            'samples.create',
            'samples.update',
        ];

        $role->syncPermissions(
            Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $permissions)
                ->get()
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $role = Role::query()
            ->where('name', 'scientific visitor')
            ->where('guard_name', 'web')
            ->first();

        if (!$role) {
            return;
        }

        $permissions = [
            'doctors.view',
            'doctors.create',
            'doctors.update',
            'doctors.restore',
            'doctors.change_status',
            'visits.view',
            'visits.create',
            'visits.update',
            'visits.complete',
            'visits.cancel',
            'samples.view',
            'samples.create',
            'samples.update',
            'products.view',
        ];

        $role->syncPermissions(
            Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $permissions)
                ->get()
        );
    }
};
