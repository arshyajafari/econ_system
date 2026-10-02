<?php

namespace App\Http\Requests\CustomerTransaction;

use App\Http\Requests\BaseFormRequest;

class ImportCustomerOpeningBalancesRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
            'opening_date' => [
                'required',
                'date',
            ],
            'mode' => [
                'required',
                'in:preview,commit',
            ],
        ];
    }
}
