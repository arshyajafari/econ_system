<?php

    namespace App\Http\Requests\Doctor;

    use App\Enums\DoctorStatus;
    use App\Http\Requests\BaseFormRequest;
    use Illuminate\Validation\Rule;

    class ChangeDoctorStatusRequest extends BaseFormRequest {
        public function rules(): array {
            return [
                'status' => [
                    'required',
                    Rule::enum(DoctorStatus::class),
                ],
            ];
        }

        public function status(): DoctorStatus {
            return DoctorStatus::from($this->validated('status'));
        }
    }
