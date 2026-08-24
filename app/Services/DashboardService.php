<?php

    namespace App\Services;

    use App\Enums\OrderReturnStatus;
    use App\Enums\PaymentStatus;
    use App\Models\Customer;
    use App\Models\Employee;
    use App\Models\Order;
    use App\Models\OrderReturn;
    use App\Models\Payment;
    use App\Models\Product;

    class DashboardService {
        public function build(): array {
            return [
                'customers' => $this->customerStats(),
                'employees' => $this->employeeStats(),
                'products' => $this->productStats(),
                'orders' => $this->orderStats(),
                'payments' => $this->paymentStats(),
                'returns' => $this->returnStats(),
            ];
        }

        protected function customerStats(): array {
            return [
                'total' => Customer::query()->count(),
                'active' => Customer::query()->where('status', 'active')->count(),
            ];
        }

        protected function employeeStats(): array {
            return [
                'total' => Employee::query()->count(),
                'active' => Employee::query()->where('status', 'active')->count(),
            ];
        }

        protected function productStats(): array {
            return [
                'total' => Product::query()->count(),
                'active' => Product::query()->where('status', 'active')->count(),
            ];
        }

        protected function orderStats(): array {
            return [
                'total' => Order::query()->count(),
                'today' => Order::query()->whereDate('created_at', today())->count(),
                'this_month' => Order::query()->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)->count(),
                'completed' => Order::query()->where('status', 'completed')->count(),
            ];
        }

        protected function paymentStats(): array {
            return [
                'total' => Payment::query()->count(),
                'pending' => Payment::query()->where('status', PaymentStatus::PENDING)->count(),
                'confirmed' => Payment::query()->where('status', PaymentStatus::CONFIRMED)->count(),
                'confirmed_amount' => Payment::query()->where('status', PaymentStatus::CONFIRMED)->sum('amount'),
                'today_amount' => Payment::query()->where('status', PaymentStatus::CONFIRMED)
                    ->whereDate('payment_date', today())->sum('amount'),
                'this_month_amount' => Payment::query()->where('status', PaymentStatus::CONFIRMED)
                    ->whereYear('payment_date', now()->year)->whereMonth('payment_date', now()->month)->sum('amount'),
            ];
        }

        protected function returnStats(): array {
            return [
                'total' => OrderReturn::query()->count(),
                'pending' => OrderReturn::query()->where('status', OrderReturnStatus::PENDING)->count(),
                'confirmed' => OrderReturn::query()->where('status', OrderReturnStatus::CONFIRMED)->count(),
                'completed' => OrderReturn::query()->where('status', OrderReturnStatus::COMPLETED)->count(),
                'today' => OrderReturn::query()->whereDate('completed_at', today())
                    ->where('status', OrderReturnStatus::COMPLETED)->count(),
            ];
        }
    }
