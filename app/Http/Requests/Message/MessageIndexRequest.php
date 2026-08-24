<?php

    namespace App\Http\Requests\Message;

    use App\Enums\MessageStatus;
    use App\Http\Requests\IndexRequest;
    use Illuminate\Validation\Rule;

    class MessageIndexRequest extends IndexRequest {
        public function rules(): array {
            return [
                ...$this->commonRules(),
                'status' => [
                    'nullable',
                    Rule::enum(MessageStatus::class),
                ],
                'sender_id' => [
                    'nullable',
                    'string',
                    'exists:users,public_id',
                ],
                'recipient_id' => [
                    'nullable',
                    'string',
                    'exists:users,public_id',
                ],
            ];
        }
    }
