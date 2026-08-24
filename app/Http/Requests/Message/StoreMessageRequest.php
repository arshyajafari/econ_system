<?php

    namespace App\Http\Requests\Message;

    use App\Http\Requests\CrudRequest;
    use App\Validation\ValidationRules;

    class StoreMessageRequest extends CrudRequest {
        public function rules(): array {
            return [
                'recipient_id' => [
                    'required',
                    'string',
                    'exists:users,public_id',
                ],
                'subject' => [
                    'nullable',
                    'string',
                    'max:200',
                ],
                'body' => [
                    'required',
                    'string',
                    'max:10000',
                ],
                ...ValidationRules::description()
            ];
        }
    }
