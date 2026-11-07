<?php

use App\\Enums\\Role;
use App\\Security\\PermissionSyncService;
use Illuminate\\Database\\Migrations\\Migration;
use Spatie\\Permission\\Models\\Role as SpatieRole;

return new class extends Migration {
    public function up(): void
    {
        app(PermissionSyncService::class)->sync();

        $grants = [
            Role::ADMIN->value => [
                'daily_activity_reports.delete_own',
                'daily_activity_reports.delete_all',
            ],
            Role::SALES_VISITOR->value => [
                'daily_activity_reports.delete_own',
            ],
            Role::SCIENTIFIC_VISITOR->value => [
                'daily_activity_reports.delete_own',
            ],
        ];

        foreach ($grants as $roleName => $permissions) {
            SpatieRole::findOrCreate($roleName, 'web')->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        // Keep permission definitions and grants intact during rollback.
    }
};
