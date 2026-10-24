<?php

namespace App\Policies;

use App\Models\Sample;
use App\Models\User;

class SamplePolicy
{
    private function isScientificVisitor(User $user): bool
    {
        return $user->hasRole('scientific visitor');
    }

    private function isScientificVisitorSample(Sample $sample): bool
    {
        return $sample->visit?->employee?->user?->hasRole('scientific visitor') ?? false;
    }

    private function ownsSample(User $user, Sample $sample): bool
    {
        return $this->isScientificVisitor($user)
            && (int) $sample->visit?->employee_id === (int) $user->employee?->id;
    }

    public function viewAny(User $user): bool { return $this->isScientificVisitor($user) || $user->can('samples.view'); }

    public function view(User $user, Sample $sample): bool
    {
        if ($this->isScientificVisitorSample($sample)) {
            // Admins can inspect all sample records; scientific visitors can
            // inspect only records created under their own visits.
            return $user->hasRole('admin') || $this->ownsSample($user, $sample);
        }

        return $user->can('samples.view') || $this->ownsSample($user, $sample);
    }

    public function create(User $user): bool { return $this->isScientificVisitor($user) || $user->can('samples.create'); }

    public function update(User $user, Sample $sample): bool
    {
        return $user->can('samples.update') && ($user->hasAnyRole(['admin', 'accountant']) || $this->ownsSample($user, $sample));
    }

    public function delete(User $user, Sample $sample): bool
    {
        return $user->hasRole('admin') || $user->can('samples.delete');
    }

    public function export(User $user): bool { return $user->can('samples.export'); }
}
