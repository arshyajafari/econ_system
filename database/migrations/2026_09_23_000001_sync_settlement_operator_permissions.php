<?php

use App\Security\PermissionSyncService;
use App\Security\RoleSyncService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        // Keep persisted Spatie permissions synchronized with RoleRegistry so
        // existing settlement-operator accounts receive the current payment
        // permissions without requiring manual role re-creation.
        app(PermissionSyncService::class)->sync();
        app(RoleSyncService::class)->sync();
    }

    public function down(): void
    {
        // Authorization is registry-driven; do not remove permissions on rollback.
    }
};
