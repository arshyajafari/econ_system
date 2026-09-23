<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemMessageNotification extends Notification
{
    use Queueable;

    /**
     * @param array<int, string> $targetValues
     */
    public function __construct(
        private readonly string $title,
        private readonly string $body,
        private readonly string $priority,
        private readonly int $senderId,
        private readonly string $messageId,
        private readonly string $targetType,
        private readonly array $targetValues,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'priority' => $this->priority,
            'sender_id' => $this->senderId,
            'message_id' => $this->messageId,
            'target_type' => $this->targetType,
            'target_values' => $this->targetValues,
        ];
    }
}
