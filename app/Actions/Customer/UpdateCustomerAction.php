<?php

    namespace App\Actions\Customer;

    use App\Models\Customer;
    use App\Models\CustomerAddress;
    use Illuminate\Support\Facades\DB;

    class UpdateCustomerAction {
        public function execute(Customer $customer, array $data): Customer {
            return DB::transaction(function () use ($customer, $data) {
                $addressData = $data['address'] ?? [];
                unset($data['address']);
                $customer->fill($data);
                $customer->save();

                if (!empty($addressData)) {
                    $address = $customer->defaultAddress;

                    if ($address) {
                        $address->fill($addressData);
                        $address->save();
                    } else {
                        $address = new CustomerAddress();
                        $address->fill($addressData);
                        $address->is_default = true;
                        $customer->addresses()->save($address);
                    }
                }

                return $customer->fresh(Customer::DEFAULT_RELATIONS);
            });
        }
    }
