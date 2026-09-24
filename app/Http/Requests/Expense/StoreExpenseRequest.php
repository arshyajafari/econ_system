<?php

namespace App\Http\Requests\Expense;

use App\Enums\ExpenseCategory;
use App\Http\Requests\CrudRequest;
use Illuminate\Validation\Rule;
use App\Validation\ValidationRules;

class StoreExpenseRequest extends CrudRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'category' => ['required', Rule::enum(ExpenseCategory::class)],
            'expense_date' => ['required', 'date'],
            'employee_id' => ['nullable', 'string', 'exists:employees,public_id'],
            ...ValidationRules::description(),
        ];
    }
}
