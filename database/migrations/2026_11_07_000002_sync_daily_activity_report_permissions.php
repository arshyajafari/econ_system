<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Security\PermissionSyncService;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role as SpatieRole;

return new class extends Migration {
    public function up(): void
    {
        app(PermissionSyncService::class)->sync();

        // Add only this feature's grants; do not resync every role and overwrite
        // permissions that administrators may have customized in production.
        $grants = [
            Role::ADMIN->value => [
                Permission::DAILY_ACTIVITY_REPORT_VIEW_OWN->value,
                Permission::DAILY_ACTIVITY_REPORT_VIEW_ALL->value,
                Permission::DAILY_ACTIVITY_REPORT_CREATE->value,
                Permission::DAILY_ACTIVITY_REPORT_UPDATE_OWN->value,
                Permission::DAILY_ACTIVITY_REPORT_UPDATE_ALL->value,
                Permission::DAILY_ACTIVITY_REPORT_EXPORT_OWN->value,
                Permission::DAILY_ACTIVITY_REPORT_EXPORT_ALL->value,
                Permission::DAILY_ACTIVITY_REPORT_CLEANUP->value,
            ],
            Role::SALES_VISITOR->value => [
                Permission::DAILY_ACTIVITY_REPORT_VIEW_OWN->value,
                Permission::DAILY_ACTIVITY_REPORT_CREATE->value,
                Permission::DAILY_ACTIVITY_REPORT_UPDATE_OWN->value,
                Permission::DAILY_ACTIVITY_REPORT_EXPORT_OWN->value,
            ],
            Role::SCIENTIFIC_VISITOR->value => [
                Permission::DAILY_ACTIVITY_REPORT_VIEW_OWN->value,
                Permission::DAILY_ACTIVITY_REPORT_CREATE->value,
                Permission::DAILY_ACTIVITY_REPORT_UPDATE_OWN->value,
                Permission::DAILY_ACTIVITY_REPORT_EXPORT_OWN->value,
            ],
        ];

        foreach ($grants as $roleName => $permissions) {
            SpatieRole::findOrCreate($roleName, 'web')->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        // Keep registry-driven permissions intact during rollback.
    }
};
