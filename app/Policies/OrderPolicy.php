<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    private function isAdmin(User $user): bool { return $user->hasRole('admin'); }
    private function isAccountant(User $user): bool { return $user->hasRole('accountant'); }

    private function ownsOrder(User $user, Order $order): bool
    {
        return $user->employee?->is($order->salesEmployee) ?? false;
    }

    private function editableBeforeAdminApproval(Order $order): bool
    {
        return in_array($order->status->value, ['draft', 'pending'], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user)
            || $this->isAccountant($user)
            || $user->can('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $this->isAdmin($user)
            || $this->isAccountant($user)
            || ($user->can('orders.view') && $this->ownsOrder($user, $order));
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user)
            || ($user->hasRole('sales visitor') && $user->can('orders.create'));
    }

    public function update(User $user, Order $order): bool
    {
        return $this->isAdmin($user)
            || ($this->isAccountant($user) && $user->can('orders.update'))
            || ($user->can('orders.update') && $this->ownsOrder($user, $order) && $this->editableBeforeAdminApproval($order));
    }

    public function submit(User $user, Order $order): bool
    {
        return $this->isAdmin($user)
            || ($user->can('orders.submit') && $this->ownsOrder($user, $order) && $order->status->value === 'draft');
    }

    public function confirm(User $user, Order $order): bool
    {
        return $this->isAdmin($user) && $user->can('orders.confirm');
    }

    public function complete(User $user, Order $order): bool
    {
        return $this->isAdmin($user) && $user->can('orders.complete');
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->isAdmin($user)
            || ($this->isAccountant($user) && $user->can('orders.cancel'))
            || ($user->can('orders.cancel') && $this->ownsOrder($user, $order) && $this->editableBeforeAdminApproval($order));
    }

    public function export(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('orders.export');
    }
}