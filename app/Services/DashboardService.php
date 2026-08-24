<?php

    namespace App\Services;

    use App\Enums\CustomerTransactionType;
    use App\Enums\InvoiceStatus;
    use App\Enums\OrderReturnStatus;
    use App\Enums\PaymentStatus;
    use App\Models\CustomerTransaction;
    use App\Models\Delivery;
    use App\Models\Invoice;
    use App\Models\Order;
    use App\Models\OrderReturn;
    use App\Models\Payment;
    use App\Models\Sample;
    use App\Models\Visit;

    class DashboardService {
        public function summary(): array {
            return [
                'sales' => $this->salesStats(),
                'orders' => $this->orderStats(),
                'payments' => $this->paymentStats(),
                'receivables' => $this->receivableStats(),
                'returns' => $this->returnStats(),
                'deliveries' => $this->deliveryStats(),
                'visits' => $this->visitStats(),
                'samples' => $this->sampleStats(),
                'recent' => $this->recentStats(),
            ];
        }

        protected function salesStats(): array {
            $query = Invoice::query()->where('status', InvoiceStatus::ISSUED);

            return [
                'today' => (float)(clone $query)->whereDate('issued_at', today())->sum('total_amount'),

                'month' => (float)(clone $query)->whereYear('issued_at', now()->year)
                    ->whereMonth('issued_at', now()->month)->sum('total_amount'),

                'year' => (float)(clone $query)->whereYear('issued_at', now()->year)->sum('total_amount'),
            ];
        }

        protected function orderStats(): array {
            return [
                'today' => Order::query()->whereDate('ordered_at', today())->count(),

                'month' => Order::query()->whereYear('ordered_at', now()->year)->whereMonth('ordered_at', now()->month)
                    ->count(),
            ];
        }

        protected function paymentStats(): array {
            $query = Payment::query()->where('status', PaymentStatus::CONFIRMED);

            return [
                'today' => (float)(clone $query)->whereDate('payment_date', today())->sum('amount'),

                'month' => (float)(clone $query)->whereYear('payment_date', now()->year)
                    ->whereMonth('payment_date', now()->month)->sum('amount'),
            ];
        }

        protected function receivableStats(): array {
            $debit = CustomerTransaction::query()->where('type', CustomerTransactionType::DEBIT)->sum('amount');

            $credit = CustomerTransaction::query()->where('type', CustomerTransactionType::CREDIT)->sum('amount');

            return [
                'total' => (float)$debit - (float)$credit,
            ];
        }

        protected function returnStats(): array {
            return [
                'pending' => OrderReturn::query()->where('status', OrderReturnStatus::PENDING)->count(),

                'confirmed' => OrderReturn::query()->where('status', OrderReturnStatus::CONFIRMED)->count(),
            ];
        }

        protected function deliveryStats(): array {
            return [
                'pending' => Delivery::query()->where('status', 'pending')->count(),

                'shipped' => Delivery::query()->where('status', 'shipped')->count(),
            ];
        }

        protected function visitStats(): array {
            return [
                'today' => Visit::query()->whereDate('visit_date', today())->count(),

                'month' => Visit::query()->whereYear('visit_date', now()->year)->whereMonth('visit_date', now()->month)
                    ->count(),
            ];
        }

        protected function sampleStats(): array {
            return [
                'today' => (int)Sample::query()->whereHas('visit', function ($query) {
                    $query->whereDate('visit_date', today());
                })->sum('quantity'),

                'month' => (int)Sample::query()->whereHas('visit', function ($query) {
                    $query->whereYear('visit_date', now()->year)->whereMonth('visit_date', now()->month);
                })->sum('quantity'),
            ];
        }

        protected function recentStats(): array {
            return [
                'orders' => Order::query()->latest('created_at')->limit(5)->get()->map(fn(Order $order) => [
                    'id' => $order->public_id,
                    'code' => $order->code,
                    'status' => $order->status?->value,
                    'created_at' => $order->created_at?->toISOString(),
                ])->values()->all(),

                'payments' => Payment::query()->with('customer')->latest('created_at')->limit(5)->get()
                    ->map(fn(Payment $payment) => [
                        'id' => $payment->public_id,
                        'reference_number' => $payment->reference_number,
                        'amount' => $payment->amount,
                        'status' => $payment->status?->value,
                        'customer' => $payment->customer ? [
                            'id' => $payment->customer->public_id,
                            'name' => $payment->customer->customer_name,
                        ] : null,
                        'created_at' => $payment->created_at?->toISOString(),
                    ])->values()->all(),

                'returns' => OrderReturn::query()->latest('created_at')->limit(5)->get()
                    ->map(fn(OrderReturn $return) => [
                        'id' => $return->public_id,
                        'code' => $return->code,
                        'status' => $return->status?->value,
                        'created_at' => $return->created_at?->toISOString(),
                    ])->values()->all(),

                'visits' => Visit::query()->with('doctor')->latest('visit_date')->limit(5)->get()
                    ->map(fn(Visit $visit) => [
                        'id' => $visit->public_id,
                        'visit_date' => $visit->visit_date?->toISOString(),
                        'status' => $visit->status?->value,
                        'doctor' => $visit->doctor ? [
                            'id' => $visit->doctor->public_id,
                            'name' => trim($visit->doctor->first_name . ' ' . $visit->doctor->last_name),
                        ] : null,
                    ])->values()->all(),
            ];
        }
    }
