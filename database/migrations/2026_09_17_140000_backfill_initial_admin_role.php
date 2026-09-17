<?php

use App\Enums\Role;
use App\Models\User;
use App\Security\PermissionSyncService;
use App\Security\RoleSyncService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionSyncService::class)->sync();
        app(RoleSyncService::class)->sync();

        $user = User::query()->find(1);

        if ($user) {
            $user->assignRole(Role::ADMIN->value);
        }
    }

    public function down(): void
    {
        $user = User::query()->find(1);

        if ($user) {
            $user->removeRole(Role::ADMIN->value);
        }
    }
};
