<?php

use App\Security\PermissionSyncService;
use App\Security\RoleSyncService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        // Persist the registry changes for existing accounts. This grants
        // accountants the complete read-only page visibility defined by the
        // registry while removing approval permissions that are now admin-only.
        app(PermissionSyncService::class)->sync();
        app(RoleSyncService::class)->sync();
    }

    public function down(): void
    {
        // Authorization is registry-driven; do not remove persisted roles or
        // permissions on rollback.
    }
};
