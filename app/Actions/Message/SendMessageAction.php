<?php

    namespace App\Actions\Message;

    use App\Enums\MessageStatus;
    use App\Models\Message;
    use App\Models\User;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class SendMessageAction {
        public function execute(array $data, User $sender): Message {
            return DB::transaction(function () use ($data, $sender) {
                $recipient = User::query()->where('public_id', $data['recipient_id'])->firstOrFail();

                if ((int)$recipient->id === (int)$sender->id) {
                    throw new RuntimeException('ارسال پیام به خودتان مجاز نیست.');
                }

                $message = Message::create([
                    'sender_id' => $sender->id,
                    'recipient_id' => $recipient->id,
                    'status' => MessageStatus::UNREAD,
                    'subject' => $data['subject'] ?? null,
                    'body' => $data['body'],
                    'description' => $data['description'] ?? null,
                ]);

                return $message->fresh(Message::DEFAULT_RELATIONS);
            });
        }
    }
