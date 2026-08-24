<?php

    namespace App\Queries\Message;

    use App\Models\Message;
    use App\Queries\BaseQuery;

    class MessageQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = Message::query()->with(Message::DEFAULT_RELATIONS);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, Message::SEARCHABLE);
            $this->applyStatus($filters['status'] ?? null);
            $this->applySender($filters['sender_id'] ?? null);
            $this->applyRecipient($filters['recipient_id'] ?? null);
            $this->applySort($filters['sort'] ?? null, Message::SORTABLE, 'created_at');

            return $this;
        }

        protected function applyStatus(?string $status): void {
            if (!$status) {
                return;
            }

            $this->query->where('status', $status);
        }

        protected function applySender(?string $senderId): void {
            if (!$senderId) {
                return;
            }

            $this->query->whereHas('sender', function ($query) use ($senderId) {
                $query->where('public_id', $senderId);
            });
        }

        protected function applyRecipient(?string $recipientId): void {
            if (!$recipientId) {
                return;
            }

            $this->query->whereHas('recipient', function ($query) use ($recipientId) {
                $query->where('public_id', $recipientId);
            });
        }
    }
