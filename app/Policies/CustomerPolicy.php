<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    private function canEdit(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accountant']);
    }

    public function viewAny(User $user): bool { return $user->can('customers.view'); }
    public function view(User $user, Customer $customer): bool { return $user->can('customers.view'); }
    public function create(User $user): bool { return $this->canEdit($user); }
    public function update(User $user, Customer $customer): bool { return $this->canEdit($user); }

    // Customer deletion is intentionally restricted to administrators only.
    public function delete(User $user, Customer $customer): bool { return $user->hasRole('admin'); }

    public function restore(User $user, Customer $customer): bool { return $user->hasRole('admin'); }
    public function changeStatus(User $user, Customer $customer): bool { return $user->hasRole('admin'); }
    public function export(User $user): bool { return $user->hasRole('admin'); }
}
