<?php

    namespace App\Actions\Customer;

    use App\Exceptions\BusinessRuleException;
    use App\Models\Customer;
    use App\Models\CustomerTransaction;
    use App\Models\Delivery;
    use App\Models\Invoice;
    use App\Models\Order;
    use App\Models\OrderReturn;
    use App\Models\Payment;
    use Illuminate\Support\Facades\DB;

    class DeleteCustomerAction {
        public function execute(Customer $customer): void {
            DB::transaction(function () use ($customer) {
                $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);

                $hasOperationalHistory = $customer->addresses()->exists() || $customer->assignments()
                        ->exists() || Delivery::query()->where('customer_id', $customer->id)->exists() || Order::query()
                        ->where('customer_id', $customer->id)->exists() || OrderReturn::query()
                        ->where('customer_id', $customer->id)->exists() || Invoice::query()
                        ->where('customer_id', $customer->id)->exists() || Payment::query()
                        ->where('customer_id', $customer->id)->exists() || CustomerTransaction::query()
                        ->where('customer_id', $customer->id)->exists();

                if ($hasOperationalHistory) {
                    throw new BusinessRuleException('این مشتری دارای سابقه عملیاتی است و امکان حذف آن وجود ندارد.');
                }

                $customer->delete();
            });
        }
    }
