<?php

    namespace App\Policies;

    use App\Models\Invoice;
    use App\Models\User;

    class InvoicePolicy {
        public function view(User $user, Invoice $invoice): bool {
            return $user->can('invoices.view');
        }

        public function create(User $user): bool {
            return $user->can('invoices.create');
        }

        public function update(User $user, Invoice $invoice): bool {
            return $user->can('invoices.update');
        }

        public function issue(User $user, Invoice $invoice): bool {
            return $user->can('invoices.issue');
        }

        public function cancel(User $user, Invoice $invoice): bool {
            return $user->can('invoices.cancel');
        }
    }
