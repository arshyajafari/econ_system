<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    private function isAdmin(User $user): bool { return $user->hasRole('admin'); }
    private function isAccountant(User $user): bool { return $user->hasRole('accountant'); }
    private function ownsInvoice(User $user, Invoice $invoice): bool { return $user->employee?->is($invoice->employee) ?? false; }

    public function viewAny(User $user): bool { return $this->isAdmin($user) || $this->isAccountant($user) || $user->can('invoices.view'); }
    public function view(User $user, Invoice $invoice): bool { return $this->isAdmin($user) || $this->isAccountant($user) || ($user->can('invoices.view') && $this->ownsInvoice($user, $invoice)); }
    public function create(User $user): bool { return $this->isAdmin($user) || $this->isAccountant($user); }
    public function update(User $user, Invoice $invoice): bool { return $this->isAdmin($user) || ($this->isAccountant($user) && $user->can('invoices.update')); }

    // Issuing an invoice is a final approval step and therefore remains
    // administrator-only even though accountants can prepare/edit drafts.
    public function issue(User $user, Invoice $invoice): bool { return $this->isAdmin($user); }

    public function cancel(User $user, Invoice $invoice): bool { return $this->isAdmin($user) || ($this->isAccountant($user) && $user->can('invoices.cancel')); }
}
