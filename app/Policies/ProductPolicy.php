<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    private function canView(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accountant', 'sales visitor']);
    }

    private function canEdit(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accountant']);
    }

    public function viewAny(User $user): bool { return $this->canView($user); }
    public function view(User $user, Product $product): bool { return $this->canView($user); }
    public function create(User $user): bool { return $this->canEdit($user); }
    public function update(User $user, Product $product): bool { return $this->canEdit($user); }
    public function delete(User $user, Product $product): bool { return $user->hasRole('admin'); }
    public function changeStatus(User $user, Product $product): bool { return $user->hasRole('admin'); }
}
