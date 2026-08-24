<?php

    namespace App\Models;

    use App\Enums\MessageStatus;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;

    class Message extends BaseModel {
        use HasPublicId;

        public const DEFAULT_RELATIONS = [
            'sender.employee',
            'recipient.employee',
        ];

        public const SEARCHABLE = [
            'subject',
            'body',
            'description',
        ];

        public const SORTABLE = [
            'created_at',
            'read_at',
        ];

        protected $fillable = [
            'sender_id',
            'recipient_id',
            'status',
            'subject',
            'body',
            'read_at',
            'description',
            'meta',
        ];

        protected $casts = [
            'status' => MessageStatus::class,
            'read_at' => 'datetime',
            'meta' => 'array',
        ];

        public function sender(): BelongsTo {
            return $this->belongsTo(User::class, 'sender_id');
        }

        public function recipient(): BelongsTo {
            return $this->belongsTo(User::class, 'recipient_id');
        }
    }
