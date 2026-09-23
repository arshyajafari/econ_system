<?php

namespace App\Policies;

use App\Models\OrderReturn;
use App\Models\User;

class OrderReturnPolicy {
    private function isAdmin(User $user): bool { return $user->hasRole('admin'); }
    private function isAccountant(User $user): bool { return $user->hasRole('accountant'); }
    private function isDeliveryOperator(User $user): bool { return $user->hasRole('delivery operator'); }
    private function ownsReturn(User $user, OrderReturn $orderReturn): bool { return $user->employee?->is($orderReturn->employee) ?? false; }
    private function editableBeforeAdminApproval(OrderReturn $orderReturn): bool { return in_array($orderReturn->status->value, ['draft', 'pending'], true); }

    public function viewAny(User $user): bool { return $this->isAdmin($user) || $this->isAccountant($user) || $user->can('order_returns.view'); }

    public function view(User $user, OrderReturn $orderReturn): bool {
        return $this->isAdmin($user)
            || $this->isAccountant($user)
            || ($user->hasRole('sales visitor') && $this->ownsReturn($user, $orderReturn))
            || ($user->hasRole('delivery operator') && $user->can('order_returns.view'))
            || $user->can('order_returns.view');
    }

    public function create(User $user): bool {
        return $this->isAdmin($user) || ($this->isDeliveryOperator($user) && $user->can('order_returns.create')) || $user->can('order_returns.create');
    }
    public function update(User $user, OrderReturn $orderReturn): bool {
        return $this->isAdmin($user)
            || ($this->isDeliveryOperator($user) && $user->can('order_returns.update') && $this->ownsReturn($user, $orderReturn) && $this->editableBeforeAdminApproval($orderReturn))
            || ($this->isAccountant($user) && $user->can('order_returns.update'))
            || ($user->can('order_returns.update') && $this->ownsReturn($user, $orderReturn) && $this->editableBeforeAdminApproval($orderReturn));
    }
    public function submit(User $user, OrderReturn $orderReturn): bool {
        return $this->isAdmin($user) || ($this->isDeliveryOperator($user) && $user->can('order_returns.submit') && $this->ownsReturn($user, $orderReturn)) || ($user->can('order_returns.submit') && $this->ownsReturn($user, $orderReturn));
    }
    public function confirm(User $user, OrderReturn $orderReturn): bool { return $this->isAdmin($user) && $user->can('order_returns.confirm'); }
    public function complete(User $user, OrderReturn $orderReturn): bool { return $this->isAdmin($user) && $user->can('order_returns.complete'); }
    public function cancel(User $user, OrderReturn $orderReturn): bool {
        return $this->isAdmin($user)
            || ($this->isDeliveryOperator($user) && $user->can('order_returns.cancel') && $this->ownsReturn($user, $orderReturn) && $this->editableBeforeAdminApproval($orderReturn))
            || ($this->isAccountant($user) && $user->can('order_returns.cancel'))
            || ($user->can('order_returns.cancel') && $this->ownsReturn($user, $orderReturn) && $this->editableBeforeAdminApproval($orderReturn));
    }
    public function receive(User $user, OrderReturn $orderReturn): bool {
        return $this->isAdmin($user) || ($this->isDeliveryOperator($user) && $user->can('order_returns.view'));
    }
    public function allocate(User $user, OrderReturn $orderReturn): bool { return $this->isAdmin($user) || $this->isAccountant($user) || ($user->can('order_returns.allocate') && $this->ownsReturn($user, $orderReturn)); }
    public function export(User $user): bool { return $this->isAdmin($user) || $user->can('order_returns.export'); }
}
