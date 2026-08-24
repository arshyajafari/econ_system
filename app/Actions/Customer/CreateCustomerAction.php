<?php

    namespace App\Actions\Customer;

    use App\Contracts\CodeGeneratorInterface;
    use App\Models\Customer;
    use App\Models\CustomerAddress;
    use Illuminate\Support\Facades\DB;

    class CreateCustomerAction {
        public function __construct(private readonly CodeGeneratorInterface $codeGenerator) {
        }

        public function execute(array $data): Customer {
            return DB::transaction(function () use ($data) {
                $addressData = $data['address'] ?? [];
                unset($data['address']);
                $data['code'] = $this->codeGenerator->generate(Customer::class);
                $customer = new Customer();
                $customer->fill($data);
                $customer->save();

                if (!empty($addressData)) {
                    $address = new CustomerAddress();
                    $address->fill($addressData);
                    $address->is_default = true;
                    $customer->addresses()->save($address);
                }

                return $customer->fresh(Customer::DEFAULT_RELATIONS);
            });
        }
    }
