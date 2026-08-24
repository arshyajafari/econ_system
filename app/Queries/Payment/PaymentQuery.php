<?php

    namespace App\Queries\Payment;

    use App\Models\Payment;
    use App\Queries\BaseQuery;

    class PaymentQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = Payment::query()->with(Payment::DEFAULT_RELATIONS);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, Payment::SEARCHABLE);
            $this->applyInvoice($filters['invoice_id'] ?? null);
            $this->applyCustomer($filters['customer_id'] ?? null);
            $this->applyEmployee($filters['employee_id'] ?? null);
            $this->applyStatus($filters['status'] ?? null);
            $this->applyMethod($filters['method'] ?? null);
            $this->applyDateRange($filters['payment_from'] ?? null, $filters['payment_to'] ?? null);
            $this->applySort($filters['sort'] ?? null, Payment::SORTABLE, 'payment_date');

            return $this;
        }

        protected function applyInvoice(?string $invoiceId): void {
            if (!$invoiceId) {
                return;
            }

            $this->query->whereHas('invoice', function ($query) use ($invoiceId) {
                $query->where('public_id', $invoiceId);
            });
        }

        protected function applyCustomer(?string $customerId): void {
            if (!$customerId) {
                return;
            }

            $this->query->whereHas('customer', function ($query) use ($customerId) {
                $query->where('public_id', $customerId);
            });
        }

        protected function applyEmployee(?string $employeeId): void {
            if (!$employeeId) {
                return;
            }

            $this->query->whereHas('employee', function ($query) use ($employeeId) {
                $query->where('public_id', $employeeId);
            });
        }

        protected function applyStatus(?string $status): void {
            if (!$status) {
                return;
            }

            $this->query->where('status', $status);
        }

        protected function applyMethod(?string $method): void {
            if (!$method) {
                return;
            }

            $this->query->where('method', $method);
        }

        protected function applyDateRange(?string $from, ?string $to): void {
            if ($from) {
                $this->query->whereDate('payment_date', '>=', $from);
            }

            if ($to) {
                $this->query->whereDate('payment_date', '<=', $to);
            }
        }
    }
