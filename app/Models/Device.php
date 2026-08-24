<?php

    namespace App\Models;

    use App\Enums\DevicePlatform;
    use App\Enums\DeviceStatus;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;

    class Device extends BaseModel {
        use HasPublicId;

        protected $fillable = [
            'user_id',
            'device_id',
            'device_label',
            'platform',
            'platform_version',
            'app_version',
            'push_token',
            'last_seen_at',
            'last_sync_at',
            'last_ip',
            'status',
            'meta'
        ];

        protected function casts(): array {
            return [
                'platform' => DevicePlatform::class,
                'status' => DeviceStatus::class,
                'last_seen_at' => 'datetime',
                'last_sync_at' => 'datetime',
                'meta' => 'array',
            ];
        }

        public function user(): BelongsTo {
            return $this->belongsTo(User::class);
        }

        public function scopeActive(Builder $query): Builder {
            return $query->where('status', DeviceStatus::ACTIVE->value);
        }

        public function isActive(): bool {
            return $this->status === DeviceStatus::ACTIVE;
        }
    }
