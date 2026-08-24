<?php

    namespace App\Http\Requests\Doctor;

    use App\Enums\DoctorSpecialty;
    use App\Enums\DoctorStatus;
    use Illuminate\Validation\Rule;
    use App\Http\Requests\IndexRequest;

    class DoctorIndexRequest extends IndexRequest {
        public function rules(): array {
            return [
                ...$this->commonRules(),
                'status' => [
                    'nullable',
                    Rule::enum(DoctorStatus::class),
                ],
                'type' => [
                    'nullable',
                    Rule::enum(DoctorSpecialty::class),
                ],
            ];
        }
    }
