<?php

namespace App\Policies;

use App\Models\DailyActivityReport;
use App\Models\User;

class DailyActivityReportPolicy
{
    private function owns(User $user, DailyActivityReport $report): bool
    {
        return $user->employee !== null && (int) $user->employee->id === (int) $report->employee_id;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('daily_activity_reports.view_all')
            || $user->can('daily_activity_reports.view_own');
    }

    public function view(User $user, DailyActivityReport $report): bool
    {
        return $user->can('daily_activity_reports.view_all')
            || ($user->can('daily_activity_reports.view_own') && $this->owns($user, $report));
    }

    public function create(User $user): bool
    {
        return $user->can('daily_activity_reports.create') && $user->employee !== null;
    }

    public function update(User $user, DailyActivityReport $report): bool
    {
        return $user->can('daily_activity_reports.update_all')
            || ($user->can('daily_activity_reports.update_own') && $this->owns($user, $report));
    }

    public function export(User $user): bool
    {
        return $user->can('daily_activity_reports.export_all')
            || $user->can('daily_activity_reports.export_own');
    }

    public function cleanup(User $user): bool
    {
        return $user->can('daily_activity_reports.cleanup');
    }
}
