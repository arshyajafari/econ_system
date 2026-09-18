<?php

namespace App\Policies;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    private function isAdmin(User $user): bool { return $user->hasRole('admin'); }
    private function ownsPayment(User $user, Payment $payment): bool { return $user->employee?->is($payment->employee) ?? false; }

    public function viewAny(User $user): bool { return $this->isAdmin($user) || $user->can('payments.view'); }
    public function view(User $user, Payment $payment): bool { return $this->isAdmin($user) || ($user->can('payments.view') && $this->ownsPayment($user, $payment)); }
    public function create(User $user): bool { return $user->can('payments.create'); }
    public function update(User $user, Payment $payment): bool { return ($this->isAdmin($user) || ($user->can('payments.update') && $this->ownsPayment($user, $payment))) && $payment->status === PaymentStatus::PENDING; }
    public function confirm(User $user, Payment $payment): bool { return ($this->isAdmin($user) || ($user->can('payments.confirm') && $this->ownsPayment($user, $payment))) && $payment->status === PaymentStatus::PENDING; }
    public function cancel(User $user, Payment $payment): bool { return ($this->isAdmin($user) || ($user->can('payments.cancel') && $this->ownsPayment($user, $payment))) && $payment->status === PaymentStatus::PENDING; }
    public function delete(User $user, Payment $payment): bool { return ($this->isAdmin($user) || ($user->can('payments.delete') && $this->ownsPayment($user, $payment))) && $payment->status === PaymentStatus::PENDING; }
}
