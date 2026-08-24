<?php

    namespace App\Actions\Employee;

    use App\Contracts\CodeGeneratorInterface;
    use App\Models\Employee;
    use App\Models\EmployeeAddress;
    use Illuminate\Support\Facades\DB;

    class CreateEmployeeAction {
        public function __construct(private readonly CodeGeneratorInterface $codeGenerator) {
        }

        public function execute(array $data): Employee {
            return DB::transaction(function () use ($data) {
                $addressData = $data['address'] ?? [];
                unset($data['address']);
                $data['code'] = $this->codeGenerator->generate(Employee::class);
                $employee = new Employee();
                $employee->fill($data);
                $employee->save();

                if (!empty($addressData)) {
                    $address = new EmployeeAddress();
                    $address->fill($addressData);
                    $address->is_default = true;
                    $employee->addresses()->save($address);
                }

                return $employee->fresh(Employee::DEFAULT_RELATIONS);
            });
        }
    }
