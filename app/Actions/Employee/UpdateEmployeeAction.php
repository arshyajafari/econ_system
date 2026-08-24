<?php

    namespace App\Actions\Employee;

    use App\Models\Employee;
    use App\Models\EmployeeAddress;
    use Illuminate\Support\Facades\DB;

    class UpdateEmployeeAction {
        public function execute(Employee $employee, array $data): Employee {
            return DB::transaction(function () use ($employee, $data) {
                $addressData = $data['address'] ?? [];
                unset($data['address']);
                $employee->fill($data);
                $employee->save();

                if (!empty($addressData)) {
                    $address = $employee->defaultAddress;

                    if (!$address) {
                        $address = new EmployeeAddress();
                        $address->fill($addressData);
                        $address->is_default = true;
                        $employee->addresses()->save($address);
                    } else {
                        $address->fill($addressData);
                        $address->save();
                    }
                }

                return $employee->fresh(Employee::DEFAULT_RELATIONS);
            });
        }
    }
