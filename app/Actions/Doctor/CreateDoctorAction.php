<?php

namespace App\Actions\Doctor;

use App\Models\Doctor;
use App\Models\DoctorAddress;
use Illuminate\Support\Facades\DB;

class CreateDoctorAction
{
    public function execute(array $data): Doctor
    {
        return DB::transaction(function () use ($data) {
            $addressData = $data['address'] ?? [];
            unset($data['address']);

            $data['code'] = Doctor::generateCode();

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
