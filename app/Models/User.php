<?php

    namespace App\Models;

    use App\Enums\UserStatus;
    use App\Traits\HasCodeGenerator;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Notifications\Notifiable;
    use Laravel\Sanctum\HasApiTokens;
    use Spatie\Permission\Traits\HasRoles;

    class User extends BaseAuthenticatable {
        use HasPublicId;
        use HasApiTokens;
        use Notifiable;
        use HasCodeGenerator;
        use HasRoles;

        protected $fillable = [
            'employee_id',
            'login',
            'password',
            'status',
            'last_login_at',
            'last_login_ip',
        ];

        protected $hidden = [
            'password',
            'remember_token',
        ];

        protected string $guard_name = 'web';

        protected function casts(): array {
            return array_merge(parent::casts(), [
                'status' => UserStatus::class,
                'last_login_at' => 'immutable_datetime',
                'password' => 'hashed',
            ]);
        }

        public function employee(): BelongsTo {
            return $this->belongsTo(Employee::class);
        }

        public function devices(): HasMany {
            return $this->hasMany(Device::class);
        }

        public function scopeActive(Builder $query): Builder {
            return $query->where('status', UserStatus::ACTIVE->value);
        }

        public function scopeInactive(Builder $query): Builder {
            return $query->where('status', UserStatus::INACTIVE->value);
        }

        public function isActive(): bool {
            return $this->status === UserStatus::ACTIVE;
        }

        public function isInactive(): bool {
            return $this->status === UserStatus::INACTIVE;
        }
    }
