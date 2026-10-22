<?php

namespace App\Policies;

use App\Enums\EmployeeActivityType;
use App\Models\Doctor;
use App\Models\User;

class DoctorPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canViewDoctors($user);
    }

    public function view(User $user, Doctor $doctor): bool
    {
        return $this->canViewDoctors($user);
    }

    public function create(User $user): bool
    {
        return $user->can('doctors.create') || $this->hasScientificVisitorActivity($user);
    }

    public function update(User $user, Doctor $doctor): bool
    {
        return $user->can('doctors.update') || $this->hasScientificVisitorActivity($user);
    }

    public function delete(User $user, Doctor $doctor): bool
    {
        return $user->hasRole('admin') && $user->can('doctors.delete');
    }

    public function restore(User $user, Doctor $doctor): bool
    {
        return $user->can('doctors.restore') || $this->hasScientificVisitorActivity($user);
    }

    public function changeStatus(User $user, Doctor $doctor): bool
    {
        return $user->can('doctors.change_status') || $this->hasScientificVisitorActivity($user);
    }

    public function export(User $user): bool
    {
        return $user->can('doctors.export') || $this->hasScientificVisitorActivity($user);
    }

    private function hasScientificVisitorActivity(User $user): bool
    {
        $employee = $user->employee;
        if (!$employee) {
            return false;
        }

        if ($employee->activity_type === EmployeeActivityType::SCIENTIFIC_VISITOR) {
            return true;
        }

        return $employee->activities()
            ->where('activity_type', EmployeeActivityType::SCIENTIFIC_VISITOR->value)
            ->exists();
    }

    private function canViewDoctors(User $user): bool
    {
        return $user->can('doctors.view') || $this->hasScientificVisitorActivity($user);
    }
}
