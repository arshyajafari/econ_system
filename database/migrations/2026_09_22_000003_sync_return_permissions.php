<?php

use App\Security\PermissionSyncService;
use App\Security\RoleSyncService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        // RoleRegistry is the source of truth. Re-sync existing Spatie roles so
        // delivery operators created before the latest registry change receive
        // order_returns.create/update/submit/cancel as well.
        app(PermissionSyncService::class)->sync();
        app(RoleSyncService::class)->sync();
    }

    public function down(): void
    {
        // Authorization is registry-driven; do not remove permissions on rollback.
    }
};
