<?php

use App\Security\PermissionRegistry;
use App\Security\RoleRegistry;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        foreach (PermissionRegistry::names() as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        foreach (RoleRegistry::permissions() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }
    }

    public function down(): void
    {
        // Authorization registry synchronization is intentionally not reversed.
    }
};
