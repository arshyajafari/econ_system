<?php

    namespace App\Actions\Customer;

    use App\Models\Customer;

    class ShowCustomerAction {
        public function execute(Customer $customer): Customer {
            return $customer->loadMissing(Customer::DEFAULT_RELATIONS);
        }
    }
