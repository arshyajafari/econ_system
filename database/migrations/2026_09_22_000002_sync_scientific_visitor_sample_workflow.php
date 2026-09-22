<?php

use App\Enums\Permission;
use App\Enums\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role as SpatieRole;

return new class extends Migration {
    public function up(): void
    {
        $role = SpatieRole::query()
            ->where('name', Role::SCIENTIFIC_VISITOR->value)
            ->where('guard_name', 'web')
            ->first();

        if (!$role) {
            return;
        }

        $role->syncPermissions([
            Permission::DOCTOR_VIEW->value,
            Permission::VISIT_VIEW->value,
            Permission::VISIT_CREATE->value,
            Permission::VISIT_UPDATE->value,
            Permission::VISIT_COMPLETE->value,
            Permission::VISIT_CANCEL->value,
            Permission::SAMPLE_VIEW->value,
            Permission::SAMPLE_CREATE->value,
            Permission::SAMPLE_UPDATE->value,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $role = SpatieRole::query()
            ->where('name', Role::SCIENTIFIC_VISITOR->value)
            ->where('guard_name', 'web')
            ->first();

        if (!$role) {
            return;
        }

        $role->syncPermissions([
            Permission::DOCTOR_VIEW->value,
            Permission::PRODUCT_VIEW->value,
            Permission::ORDER_VIEW->value,
            Permission::ORDER_CREATE->value,
            Permission::ORDER_UPDATE->value,
            Permission::ORDER_SUBMIT->value,
            Permission::VISIT_VIEW->value,
            Permission::VISIT_CREATE->value,
            Permission::VISIT_UPDATE->value,
            Permission::VISIT_COMPLETE->value,
            Permission::VISIT_CANCEL->value,
            Permission::SAMPLE_VIEW->value,
            Permission::SAMPLE_CREATE->value,
            Permission::SAMPLE_UPDATE->value,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};