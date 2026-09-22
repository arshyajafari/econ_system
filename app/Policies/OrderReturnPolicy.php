<?php

namespace App\Policies;

use App\Models\OrderReturn;
use App\Models\User;

class OrderReturnPolicy
{
    private function isAdmin(User $user): bool { return $user->hasRole('admin'); }
    private function isAccountant(User $user): bool { return $user->hasRole('accountant'); }

    private function ownsReturn(User $user, OrderReturn $orderReturn): bool
    {
        return $user->employee?->is($orderReturn->employee) ?? false;
    }

    private function editableBeforeAdminApproval(OrderReturn $orderReturn): bool
    {
        return in_array($orderReturn->status->value, ['draft', 'pending'], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $this->isAccountant($user) || $user->can('order_returns.view');
    }

    public function view(User $user, OrderReturn $orderReturn): bool
    {
        // Delivery/settlement operators have view-only access to the complete
        // returns register, not just returns they personally created.
        return $this->isAdmin($user) || $this->isAccountant($user) || $user->can('order_returns.view');
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('order_returns.create');
    }

    public function update(User $user, OrderReturn $orderReturn): bool
    {
        return $this->isAdmin($user)
            || ($this->isAccountant($user) && $user->can('order_returns.update'))
            || ($user->can('order_returns.update') && $this->ownsReturn($user, $orderReturn) && $this->editableBeforeAdminApproval($orderReturn));
    }

    public function submit(User $user, OrderReturn $orderReturn): bool
    {
        return $this->isAdmin($user) || ($user->can('order_returns.submit') && $this->ownsReturn($user, $orderReturn));
    }

    public function confirm(User $user, OrderReturn $orderReturn): bool
    {
        return $this->isAdmin($user) && $user->can('order_returns.confirm');
    }

    public function complete(User $user, OrderReturn $orderReturn): bool
    {
        return $this->isAdmin($user) && $user->can('order_returns.complete');
    }

    public function cancel(User $user, OrderReturn $orderReturn): bool
    {
        return $this->isAdmin($user)
            || ($this->isAccountant($user) && $user->can('order_returns.cancel'))
            || ($user->can('order_returns.cancel') && $this->ownsReturn($user, $orderReturn) && $this->editableBeforeAdminApproval($orderReturn));
    }

    public function allocate(User $user, OrderReturn $orderReturn): bool
    {
        return $this->isAdmin($user) || $this->isAccountant($user) || ($user->can('order_returns.allocate') && $this->ownsReturn($user, $orderReturn));
    }

    public function export(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('order_returns.export');
    }
}
