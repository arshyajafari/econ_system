<?php

    namespace App\Actions\Customer;

    use App\Enums\CustomerStatus;
    use App\Models\Customer;
    use Illuminate\Support\Facades\DB;

    class ChangeCustomerStatusAction {
        public function execute(Customer $customer, CustomerStatus $status): Customer {
            DB::transaction(function () use ($customer, $status) {
                if ($customer->status !== $status) {

                    $customer->status = $status;

                    $customer->save();
                }
            });

            return $customer->fresh(Customer::DEFAULT_RELATIONS);
        }
    }
