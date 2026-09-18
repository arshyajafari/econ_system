<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SystemNotificationResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'title' => data_get($this->data, 'title'),
            'body' => data_get($this->data, 'body'),
            'priority' => data_get($this->data, 'priority', 'normal'),
            'is_read' => $this->read_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
            'read_at' => $this->read_at?->toIso8601String(),
            'message_id' => data_get($this->data, 'message_id'),
            'can_manage' => $request->user()?->hasRole('admin') && (int) data_get($this->data, 'sender_id') === (int) $request->user()?->getKey(),
        ];
    }
}
