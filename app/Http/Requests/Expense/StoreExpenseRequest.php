<?php

namespace App\Http\Requests\Expense;

use App\Http\Requests\CrudRequest;
use App\Validation\ValidationRules;

class StoreExpenseRequest extends CrudRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'category' => ['required', 'string', 'max:50'],
            'expense_date' => ['required', 'date'],
            'employee_id' => ['nullable', 'string', 'exists:employees,public_id'],
            ...ValidationRules::description(),
        ];
    }
}
