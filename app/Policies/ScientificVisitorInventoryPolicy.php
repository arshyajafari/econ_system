<?php

namespace App\Policies;

use App\Models\ScientificVisitorInventory;
use App\Models\User;

class ScientificVisitorInventoryPolicy
{
    private function isManager(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accountant']);
    }

    private function isScientificVisitor(User $user): bool
    {
        return $user->hasRole('scientific visitor');
    }

    public function viewAny(User $user): bool
    {
        return $this->isManager($user) || $this->isScientificVisitor($user);
    }

    public function view(User $user, ScientificVisitorInventory $inventory): bool
    {
        if ($this->isManager($user)) {
            return true;
        }

        return $this->isScientificVisitor($user)
            && (int) $inventory->employee_id === (int) $user->employee?->id;
    }

    public function create(User $user): bool
    {
        return $this->isManager($user);
    }

    public function update(User $user, ScientificVisitorInventory $inventory): bool
    {
        return $this->isManager($user);
    }

    public function delete(User $user, ScientificVisitorInventory $inventory): bool
    {
        return false;
    }
}
