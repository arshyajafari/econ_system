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

    public function viewAny(User $user): bool
    {
        return $this->isScientificVisitor($user) || $user->can('samples.view');
    }

    public function view(User $user, Sample $sample): bool
    {
        if ($this->isScientificVisitorSample($sample)) {
            return $user->hasRole('admin') || $this->ownsSample($user, $sample);
        }

        return $user->can('samples.view') || $this->ownsSample($user, $sample);
    }

    public function create(User $user): bool
    {
        return $this->isScientificVisitor($user) || $user->can('samples.create');
    }

    public function update(User $user, Sample $sample): bool
    {
        // A scientific visitor may correct samples recorded under their own
        // visits without needing a separate global samples.update permission.
        if ($this->ownsSample($user, $sample)) {
            return true;
        }

        return $user->can('samples.update')
            && $user->hasAnyRole(['admin', 'accountant']);
    }

    public function delete(User $user, Sample $sample): bool
    {
        // Restrict self-service deletion to the visitor's own samples. Other
        // users must have the established admin/delete permission.
        return $this->ownsSample($user, $sample)
            || $user->hasRole('admin')
            || $user->can('samples.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('samples.export');
    }
}
