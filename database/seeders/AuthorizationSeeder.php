<?php

    namespace Database\Seeders;

    use App\Security\PermissionSyncService;
    use App\Security\RoleSyncService;
    use Illuminate\Database\Seeder;

    class AuthorizationSeeder extends Seeder {
        public function run(PermissionSyncService $permissionSyncService, RoleSyncService $roleSyncService): void {
            $permissionSyncService->sync();
            $roleSyncService->sync();
        }
    }
