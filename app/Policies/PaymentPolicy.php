<?php

namespace App\Policies;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    private function isAdmin(User $user): bool { return $user->hasRole('admin'); }
    private function isAccountant(User $user): bool { return $user->hasRole('accountant'); }
    private function isSettlementOperator(User $user): bool { return $user->hasRole('settlement_operator'); }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        // Payment operators need to see payments created by other employees;
        // ownership is not a visibility boundary for this operational resource.
        return $this->isAdmin($user) || $user->can('payments.view');
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('payments.create');
    }

    public function update(User $user, Payment $payment): bool
    {
        if ($payment->status !== PaymentStatus::PENDING) {
            return false;
        }

        // Admins may manage pending payments. Other payment editors must have
        // both an authorized operational role and the explicit update ability.
        return $this->isAdmin($user)
            || (
                ($this->isAccountant($user) || $this->isSettlementOperator($user))
                && $user->can('payments.update')
            );
    }

    public function confirm(User $user, Payment $payment): bool
    {
        return $this->isAdmin($user)
            && $payment->status === PaymentStatus::PENDING;
    }

    public function cancel(User $user, Payment $payment): bool
    {
        return ($this->isAdmin($user) || $user->can('payments.cancel'))
            && $payment->status === PaymentStatus::PENDING;
    }

    public function delete(User $user, Payment $payment): bool
    {
        // Deletion remains restricted to administrators/accountants and pending records.
        return ($this->isAdmin($user) || $this->isAccountant($user))
            && $payment->status === PaymentStatus::PENDING;
    }
}
