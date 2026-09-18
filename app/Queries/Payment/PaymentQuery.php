<?php

namespace App\Queries\Payment;

use App\Models\Payment;
use App\Models\User;
use App\Queries\BaseQuery;

class PaymentQuery extends BaseQuery
{
    protected function initialize(): void { $this->query = Payment::query()->with(Payment::DEFAULT_RELATIONS); }

    public function apply(array $filters, ?User $user = null): static
    {
        $user ??= auth()->user();
        $this->applySearch($filters['search'] ?? null, Payment::SEARCHABLE);
        $this->applyInvoice($filters['invoice_id'] ?? null);
        $this->applyCustomer($filters['customer_id'] ?? null);

        if ($user?->hasRole('admin')) {
            $this->applyEmployee($filters['employee_id'] ?? null);
        } else {
            $employeeId = $user?->employee?->id;
            if ($employeeId) $this->query->where('employee_id', $employeeId);
            else $this->query->whereRaw('1 = 0');
        }

        $this->applyStatus($filters['status'] ?? null);
        $this->applyMethod($filters['method'] ?? null);
        $this->applyDateRange($filters['payment_from'] ?? null, $filters['payment_to'] ?? null);
        $this->applySort($filters['sort'] ?? null, Payment::SORTABLE, 'payment_date');
        return $this;
    }

    protected function applyInvoice(?string $invoiceId): void { if ($invoiceId) $this->query->whereHas('invoice', fn($query) => $query->where('public_id', $invoiceId)); }
    protected function applyCustomer(?string $customerId): void { if ($customerId) $this->query->whereHas('customer', fn($query) => $query->where('public_id', $customerId)); }
    protected function applyEmployee(?string $employeeId): void { if ($employeeId) $this->query->whereHas('employee', fn($query) => $query->where('public_id', $employeeId)); }
    protected function applyStatus(?string $status): void { if ($status) $this->query->where('status', $status); }
    protected function applyMethod(?string $method): void { if ($method) $this->query->where('method', $method); }
    protected function applyDateRange(?string $from, ?string $to): void { if ($from) $this->query->whereDate('payment_date', '>=', $from); if ($to) $this->query->whereDate('payment_date', '<=', $to); }
}
