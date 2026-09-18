<?php

namespace App\Policies;

use App\Models\OrderReturn;
use App\Models\User;

class OrderReturnPolicy
{
    private function isAdmin(User $user): bool { return $user->hasRole('admin'); }

    private function ownsReturn(User $user, OrderReturn $orderReturn): bool
    {
        return $user->employee?->is($orderReturn->employee) ?? false;
    }

    public function viewAny(User $user): bool { return $this->isAdmin($user) || $user->can('order_returns.view'); }
    public function view(User $user, OrderReturn $orderReturn): bool { return $this->isAdmin($user) || ($user->can('order_returns.view') && $this->ownsReturn($user, $orderReturn)); }
    public function create(User $user): bool { return $this->isAdmin($user) || $user->can('order_returns.create'); }
    public function update(User $user, OrderReturn $orderReturn): bool { return $this->isAdmin($user) || ($user->can('order_returns.update') && $this->ownsReturn($user, $orderReturn)); }
    public function submit(User $user, OrderReturn $orderReturn): bool { return $this->isAdmin($user) || ($user->can('order_returns.submit') && $this->ownsReturn($user, $orderReturn)); }
    public function confirm(User $user, OrderReturn $orderReturn): bool { return $this->isAdmin($user) || ($user->can('order_returns.confirm') && $this->ownsReturn($user, $orderReturn)); }
    public function complete(User $user, OrderReturn $orderReturn): bool { return $this->isAdmin($user) || ($user->can('order_returns.complete') && $this->ownsReturn($user, $orderReturn)); }
    public function cancel(User $user, OrderReturn $orderReturn): bool { return $this->isAdmin($user) || ($user->can('order_returns.cancel') && $this->ownsReturn($user, $orderReturn)); }
    public function allocate(User $user, OrderReturn $orderReturn): bool { return $this->isAdmin($user) || ($user->can('order_returns.allocate') && $this->ownsReturn($user, $orderReturn)); }
    public function export(User $user): bool { return $this->isAdmin($user) || $user->can('order_returns.export'); }
}
