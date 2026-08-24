<?php

    namespace App\Actions\Message;

    use App\Enums\MessageStatus;
    use App\Models\Message;
    use App\Models\User;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class ReadMessageAction {
        public function execute(Message $message, User $user): Message {
            return DB::transaction(function () use ($message, $user) {
                $message = Message::query()->lockForUpdate()->findOrFail($message->id);

                if ((int)$message->recipient_id !== (int)$user->id) {
                    throw new RuntimeException('این پیام متعلق به کاربر فعلی نیست.');
                }

                if ($message->status === MessageStatus::UNREAD) {
                    $message->status = MessageStatus::READ;
                    $message->read_at = now();
                    $message->save();
                }

                return $message->fresh(Message::DEFAULT_RELATIONS);
            });
        }
    }
