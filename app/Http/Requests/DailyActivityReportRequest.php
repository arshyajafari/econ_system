<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DailyActivityReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'visit_date' => ['required', 'date_format:Y-m-d'],
            'visit_time' => ['nullable', 'date_format:H:i'],
            'organization_name' => ['required', 'string', 'max:180'],
            'contact_name' => ['nullable', 'string', 'max:160'],
            'contact_type' => ['required', Rule::in(['in_person', 'phone'])],
            'purpose' => ['required', 'string', 'max:200'],
            'discussion' => ['required', 'string', 'max:10000'],
            'employee_question' => ['nullable', 'string', 'max:5000'],
            'audience_feedback' => ['nullable', 'string', 'max:5000'],
            'outcome' => ['required', 'string', 'max:5000'],
            'result' => ['required', Rule::in(['positive', 'neutral', 'negative', 'follow_up'])],
            'edit_reason' => [$this->isMethod('put') || $this->isMethod('patch') ? 'required' : 'nullable', 'string', 'min:5', 'max:500'],
        ];
    }
}
