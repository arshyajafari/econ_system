<?php

namespace App\Queries\Invoice;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\User;
use App\Queries\BaseQuery;

class InvoiceQuery extends BaseQuery
{
    protected function initialize(): void { $this->query = Invoice::query()->with(Invoice::DEFAULT_RELATIONS); }

    public function apply(array $filters, ?User $user = null): static
    {
        $user ??= auth()->user();
        $this->applySearch($filters['search'] ?? null, Invoice::SEARCHABLE);
        $this->applyOrder($filters['order_id'] ?? null);
        $this->applyCustomer($filters['customer_id'] ?? null);

        if ($user?->hasRole('admin')) {
            $this->applyEmployee($filters['employee_id'] ?? null);
        } else {
            $employeeId = $user?->employee?->id;
            if ($employeeId) {
                $this->query->where('employee_id', $employeeId);
            } else {
                $this->query->whereRaw('1 = 0');
            }
        }

        $this->applyStatus($filters['status'] ?? null);
        $this->applySettlement($filters['settled'] ?? null);
        $this->applyPayable($filters['payable'] ?? null);
        $this->applyDateRange($filters['issued_from'] ?? null, $filters['issued_to'] ?? null);
        $this->applySort($filters['sort'] ?? null, Invoice::SORTABLE, 'created_at');
        return $this;
    }

    protected function applyOrder(?string $orderId): void
    {
        if ($orderId) $this->query->whereHas('order', fn($query) => $query->where('public_id', $orderId));
    }

    protected function applyCustomer(?string $customerId): void
    {
        if ($customerId) $this->query->whereHas('customer', fn($query) => $query->where('public_id', $customerId));
    }

    protected function applyEmployee(?string $employeeId): void
    {
        if ($employeeId) $this->query->whereHas('employee', fn($query) => $query->where('public_id', $employeeId));
    }

    protected function applyStatus(?string $status): void
    {
        if ($status) $this->query->where('status', $status);
    }

    protected function applySettlement(?bool $settled): void
    {
        if ($settled === null) return;

        $operator = $settled ? '>=' : '<';

        $this->query->whereRaw(
            "(SELECT COALESCE(SUM(payments.amount + payments.settlement_discount_amount), 0)
                FROM payments
                WHERE payments.invoice_id = invoices.id
                  AND payments.status = ?)
             +
             (SELECT COALESCE(SUM(customer_credit_allocations.amount), 0)
                FROM customer_credit_allocations
                WHERE customer_credit_allocations.invoice_id = invoices.id)
             {$operator} invoices.total_amount",
            [
                PaymentStatus::CONFIRMED->value,
            ],
        );
    }

    /**
     * "Payable" is intentionally different from "unsettled".
     *
     * It represents whether another payment can still be entered. Pending
     * payments reserve part of the invoice balance, so an invoice with a
     * pending payment that covers its effective balance must not appear in
     * the payment form even though it is not financially settled until the
     * payment is confirmed.
     */
    protected function applyPayable(?bool $payable): void
    {
        if ($payable === null) return;

        $operator = $payable ? '>' : '<=';

        $this->query->whereRaw(
            "(invoices.total_amount
                -
                (SELECT COALESCE(SUM(payments.amount + payments.settlement_discount_amount), 0)
                    FROM payments
                    WHERE payments.invoice_id = invoices.id
                      AND payments.status IN (?, ?))
                -
                (SELECT COALESCE(SUM(customer_transactions.amount), 0)
                    FROM customer_transactions
                    INNER JOIN order_returns ON order_returns.id = customer_transactions.order_return_id
                    WHERE order_returns.order_id = invoices.order_id
                      AND order_returns.status = ?
                      AND customer_transactions.type = 'credit')
             ) {$operator} 0",
            [
                PaymentStatus::CONFIRMED->value,
                PaymentStatus::PENDING->value,
            ],
        );
    }

    protected function applyDateRange(?string $from, ?string $to): void
    {
        if ($from) $this->query->whereDate('issued_at', '>=', $from);
        if ($to) $this->query->whereDate('issued_at', '<=', $to);
    }
}
