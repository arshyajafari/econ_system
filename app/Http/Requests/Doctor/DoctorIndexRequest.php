<?php

    namespace App\Http\Requests\Doctor;

    use App\Enums\DoctorSpecialty;
    use App\Enums\DoctorStatus;
    use App\Http\Requests\IndexRequest;
    use Illuminate\Validation\Rule;

    class DoctorIndexRequest extends IndexRequest {
        public function rules(): array {
            return [
                ...$this->commonRules(),
                'status' => [
                    'nullable',
                    Rule::enum(DoctorStatus::class),
                ],
                'specialty' => [
                    'nullable',
                    Rule::enum(DoctorSpecialty::class),
                ],
            ];
        }
    }
