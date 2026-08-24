<?php

    namespace App\Actions\Doctor;

    use App\Contracts\CodeGeneratorInterface;
    use App\Models\Doctor;
    use App\Models\DoctorAddress;
    use Illuminate\Support\Facades\DB;

    class CreateDoctorAction {
        public function __construct(private readonly CodeGeneratorInterface $codeGenerator) {
        }

        public function execute(array $data): Doctor {
            return DB::transaction(function () use ($data) {
                $addressData = $data['address'] ?? [];
                unset($data['address']);
                $data['code'] = $this->codeGenerator->generate(Doctor::class);
                $doctor = new Doctor();
                $doctor->fill($data);
                $doctor->save();

                if (!empty($addressData)) {
                    $address = new DoctorAddress();
                    $address->fill($addressData);
                    $address->is_default = true;
                    $doctor->addresses()->save($address);
                }

                return $doctor->fresh(Doctor::DEFAULT_RELATIONS);
            });
        }
    }
