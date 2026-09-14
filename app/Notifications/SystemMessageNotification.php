<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemMessageNotification extends Notification {
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $body,
        private readonly string $priority,
        private readonly int $senderId,
    ) {}

    public function via(object $notifiable): array {
        return ['database'];
    }

    public function toArray(object $notifiable): array {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'priority' => $this->priority,
            'sender_id' => $this->senderId,
        ];
    }
}
