<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;

class BrandPolicy
{
    private function canView(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('sales visitor');
    }

    public function viewAny(User $user): bool { return $this->canView($user); }
    public function view(User $user, Brand $brand): bool { return $this->canView($user); }
    public function create(User $user): bool { return $user->hasRole('admin'); }
    public function update(User $user, Brand $brand): bool { return $user->hasRole('admin'); }
    public function delete(User $user, Brand $brand): bool { return $user->hasRole('admin'); }
    public function changeActivity(User $user, Brand $brand): bool { return $user->hasRole('admin'); }
}
