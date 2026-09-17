<?php

use App\Security\PermissionSyncService;
use App\Security\RoleSyncService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(PermissionSyncService $permissions, RoleSyncService $roles): void
    {
        $permissions->sync();
        $roles->sync();
    }

    public function down(): void
    {
        // Authorization is registry-driven; do not remove permissions or roles on rollback.
    }
};
