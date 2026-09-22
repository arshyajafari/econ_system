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

    private function ownsSample(User $user, Sample $sample): bool
    {
        return $this->isScientificVisitor($user)
            && (int) $sample->visit?->employee_id === (int) $user->employee?->id;
    }

    public function viewAny(User $user): bool { return $this->isScientificVisitor($user) || $user->can('samples.view'); }

    public function view(User $user, Sample $sample): bool
    {
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