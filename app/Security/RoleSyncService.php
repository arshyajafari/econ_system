<?php

    namespace App\Security;

    use Spatie\Permission\Models\Role as SpatieRole;

    class RoleSyncService {
        public function sync(): void {
            foreach (RoleRegistry::permissions() as $roleName => $permissions) {
                $role = SpatieRole::findOrCreate($roleName, 'web');

                $role->syncPermissions($permissions);
            }
        }
    }
