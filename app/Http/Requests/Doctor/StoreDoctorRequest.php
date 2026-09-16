<?php

namespace App\Http\Requests\Doctor;

use App\Enums\DoctorSpecialty;
use App\Enums\DoctorStatus;
use App\Http\Requests\BaseFormRequest;
use App\Validation\ValidationRules;
use Illuminate\Validation\Rules\Enum;

class StoreDoctorRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => [
                'required',
                'string',
                'max:100',
            ],
            'last_name' => [
                'required',
                'string',
                'max:100',
            ],
            'phone_number' => [
                'nullable',
                'string',
                'max:20',
            ],
            'clinic_name' => [
                'nullable',
                'string',
                'max:100',
            ],
            'file' => [
                'nullable',
                'string',
                'max:500',
            ],
            'specialty' => [
                'required',
                new Enum(DoctorSpecialty::class),
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'status' => [
                'required',
                new Enum(DoctorStatus::class),
            ],
            ...ValidationRules::address(),
            ...ValidationRules::meta(),
            ...ValidationRules::description(),
        ];
    }
}
