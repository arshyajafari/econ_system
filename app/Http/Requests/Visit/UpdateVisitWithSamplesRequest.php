<?php

namespace App\Http\Requests\Visit;

use App\Http\Requests\CrudRequest;

class UpdateVisitWithSamplesRequest extends CrudRequest
{
    public function rules(): array
    {
        return [
            'visit_date' => ['required', 'date'],
            'purpose' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'samples' => ['required', 'array'],
            'samples.*.id' => ['required', 'string', 'distinct'],
            'samples.*.quantity' => ['required', 'integer', 'min:1'],
            'samples.*.description' => ['nullable', 'string'],
        ];
    }
}
