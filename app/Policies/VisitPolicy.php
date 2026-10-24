<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Visit;

class VisitPolicy
{
    private function isScopedScientificVisitor(User $user): bool
    {
        return $user->hasRole('scientific visitor')
            && !$user->hasAnyRole(['admin', 'accountant']);
    }

    private function ownsVisit(User $user, Visit $visit): bool
    {
        return $user->employee !== null
            && (int) $user->employee->id === (int) $visit->employee_id;
    }

    private function canAccessVisit(User $user, Visit $visit, string $permission): bool
    {
        if (!$user->can($permission)) {
            return false;
        }

        return !$this->isScopedScientificVisitor($user) || $this->ownsVisit($user, $visit);
    }

    public function viewAny(User $user): bool
    {
        return $user->can('visits.view');
    }

    public function view(User $user, Visit $visit): bool
    {
        return $this->canAccessVisit($user, $visit, 'visits.view');
    }

    public function create(User $user): bool
    {
        return $user->can('visits.create');
    }

    public function update(User $user, Visit $visit): bool
    {
        return $this->canAccessVisit($user, $visit, 'visits.update');
    }

    public function delete(User $user, Visit $visit): bool
    {
        return $user->hasRole('admin') && $user->can('visits.delete');
    }

    public function complete(User $user, Visit $visit): bool
    {
        return $this->canAccessVisit($user, $visit, 'visits.complete');
    }

    public function cancel(User $user, Visit $visit): bool
    {
        return $this->canAccessVisit($user, $visit, 'visits.cancel');
    }

    public function export(User $user): bool
    {
        return $user->can('visits.export');
    }
}
