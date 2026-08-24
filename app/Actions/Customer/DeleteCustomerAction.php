<?php

    namespace App\Actions\Customer;

    use App\Models\Customer;
    use Illuminate\Support\Facades\DB;

    class DeleteCustomerAction {
        public function execute(Customer $customer): void {
            DB::transaction(function () use ($customer) {
                $customer->delete();
            });
        }
    }
