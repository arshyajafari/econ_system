<?php

namespace App\Policies;

use App\Models\InventoryMovement;
use App\Models\User;

class InventoryMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('sales visitor');
    }

    public function view(User $user, InventoryMovement $movement): bool
    {
        return $user->hasRole('admin') || $user->hasRole('sales visitor');
    }
}
