<?php

    use App\Http\Requests\BaseFormRequest;
    use Illuminate\Validation\Rule;

    abstract class BaseChangeStatusRequest extends BaseFormRequest {
        abstract protected function enumClass(): string;

        public function rules(): array {
            return [
                'status' => [
                    'required',
                    Rule::enum($this->enumClass()),
                ],
            ];
        }
    }
