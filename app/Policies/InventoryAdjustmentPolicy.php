<?php

namespace App\Policies;

use App\Models\InventoryAdjustment;
use App\Models\User;

class InventoryAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('sales visitor');
    }

    public function view(User $user, InventoryAdjustment $adjustment): bool
    {
        return $user->hasRole('admin') || $user->hasRole('sales visitor');
    }

    public function create(User $user): bool { return $user->hasRole('admin'); }
}
