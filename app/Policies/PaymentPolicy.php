<?php

    namespace App\Policies;

    use App\Enums\PaymentStatus;
    use App\Models\Payment;
    use App\Models\User;

    class PaymentPolicy {
        public function viewAny(User $user): bool {
            return $user->can('payments.view');
        }

        public function view(User $user, Payment $payment): bool {
            return $user->can('payments.view');
        }

        public function create(User $user): bool {
            return $user->can('payments.create');
        }

        public function update(User $user, Payment $payment): bool {
            return $user->can('payments.update') && $payment->status === PaymentStatus::PENDING;
        }

        public function confirm(User $user, Payment $payment): bool {
            return $user->can('payments.confirm') && $payment->status === PaymentStatus::PENDING;
        }

        public function cancel(User $user, Payment $payment): bool {
            return $user->can('payments.cancel') && $payment->status === PaymentStatus::PENDING;
        }

        public function delete(User $user, Payment $payment): bool {
            return $user->can('payments.delete') && $payment->status === PaymentStatus::PENDING;
        }
    }
