<?php

    namespace App\Http\Requests\Visit;

    use App\Http\Requests\CrudRequest;
    use App\Validation\ValidationRules;

    class UpdateVisitRequest extends CrudRequest
    {
        public function rules(): array
        {
            return [
                'visit_date' => [
                    'required',
                    'date',
                ],
                'purpose' => [
                    'nullable',
                    'string',
                    'max:150',
                ],
                ...ValidationRules::description(),
            ];
        }
    }
