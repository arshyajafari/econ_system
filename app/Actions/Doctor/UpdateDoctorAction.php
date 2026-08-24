<?php

    namespace App\Actions\Doctor;

    use App\Models\Doctor;
    use App\Models\DoctorAddress;
    use Illuminate\Support\Facades\DB;

    class UpdateDoctorAction {
        public function execute(Doctor $doctor, array $data): Doctor {
            return DB::transaction(function () use ($doctor, $data) {
                $addressData = $data['address'] ?? [];
                unset($data['address']);
                $doctor->fill($data);
                $doctor->save();

                if (!empty($addressData)) {
                    $address = $doctor->defaultAddress;

                    if ($address) {
                        $address->fill($addressData);
                        $address->save();
                    } else {
                        $address = new DoctorAddress();
                        $address->fill($addressData);
                        $address->is_default = true;
                        $doctor->addresses()->save($address);
                    }
                }

                return $doctor->fresh(Doctor::DEFAULT_RELATIONS);
            });
        }
    }
