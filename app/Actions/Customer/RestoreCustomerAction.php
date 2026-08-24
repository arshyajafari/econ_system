<?php

    namespace App\Actions\Customer;

    use App\Models\Customer;
    use Illuminate\Support\Facades\DB;

    class RestoreCustomerAction {
        public function execute(Customer $customer): Customer {
            return DB::transaction(function () use ($customer) {
                $customer->restore();

                return $customer->fresh(Customer::DEFAULT_RELATIONS);
            });
        }
    }
