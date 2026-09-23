<?php

namespace App\Policies;

use App\Models\ProductCategory;
use App\Models\User;

class ProductCategoryPolicy
{
    private function canView(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accountant', 'sales visitor']);
    }

    public function viewAny(User $user): bool { return $this->canView($user); }
    public function view(User $user, ProductCategory $category): bool { return $this->canView($user); }
    public function create(User $user): bool { return $user->hasRole('admin'); }
    public function update(User $user, ProductCategory $category): bool { return $user->hasRole('admin'); }
    public function delete(User $user, ProductCategory $category): bool { return $user->hasRole('admin'); }
    public function changeActivity(User $user, ProductCategory $category): bool { return $user->hasRole('admin'); }
}
