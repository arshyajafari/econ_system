<?php

    namespace App\Security;

    use Spatie\Permission\Models\Permission;

    class PermissionSyncService {
        public function sync(): void {
            foreach (PermissionRegistry::names() as $permission) {
                Permission::findOrCreate($permission, 'web');
            }
        }
    }
