<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    private function isAdmin(User $user): bool { return $user->hasRole('admin'); }
    private function ownsInvoice(User $user, Invoice $invoice): bool { return $user->employee?->is($invoice->employee) ?? false; }

    public function viewAny(User $user): bool { return $this->isAdmin($user) || $user->can('invoices.view'); }
    public function view(User $user, Invoice $invoice): bool { return $this->isAdmin($user) || ($user->can('invoices.view') && $this->ownsInvoice($user, $invoice)); }
    public function create(User $user): bool { return $user->can('invoices.create'); }
    public function update(User $user, Invoice $invoice): bool { return $this->isAdmin($user) || ($user->can('invoices.update') && $this->ownsInvoice($user, $invoice)); }
    public function issue(User $user, Invoice $invoice): bool { return $this->isAdmin($user) || ($user->can('invoices.issue') && $this->ownsInvoice($user, $invoice)); }
    public function cancel(User $user, Invoice $invoice): bool { return $this->isAdmin($user) || ($user->can('invoices.cancel') && $this->ownsInvoice($user, $invoice)); }
}
