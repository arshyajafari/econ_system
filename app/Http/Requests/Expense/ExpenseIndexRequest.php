<?php

namespace App\Http\Requests\Expense;

use App\Http\Requests\CrudRequest;
use Illuminate\Validation\Rule;

class ExpenseIndexRequest extends CrudRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'employee_id' => ['nullable', 'string', 'exists:employees,public_id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'string', Rule::in(['expense_date', 'amount', 'created_at', 'title'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
