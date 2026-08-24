<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class MessageResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'sender' => $this->whenLoaded('sender', fn() => [
                    'id' => $this->sender->public_id,
                    'name' => $this->userName($this->sender),
                ]),
                'recipient' => $this->whenLoaded('recipient', fn() => [
                    'id' => $this->recipient->public_id,
                    'name' => $this->userName($this->recipient),
                ]),
                'status' => $this->status?->value,
                'subject' => $this->subject,
                'body' => $this->body,
                'read_at' => $this->read_at?->toISOString(),
                'description' => $this->description,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ];
        }

        protected function userName($user): string {
            if ($user->employee) {
                return trim($user->employee->first_name . ' ' . $user->employee->last_name);
            }

            return $user->email;
        }
    }
