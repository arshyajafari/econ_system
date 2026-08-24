<?php

    namespace App\Models;

    use App\Contracts\HasGeneratedCode;
    use App\Enums\CustomerStatus;
    use App\Enums\CustomerType;
    use App\Services\CodeGeneratorData;
    use App\Traits\HasAudit;
    use App\Traits\HasCodeGenerator;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Database\Eloquent\Relations\HasOne;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class Customer extends BaseModel implements HasGeneratedCode {
        use HasPublicId, HasFactory, HasCodeGenerator, SoftDeletes, HasAudit;

        public const DEFAULT_RELATIONS = [
            'defaultAddress',
        ];

        public const SEARCHABLE = [
            'code',
            'customer_name',
            'owner_name',
            'manager_name',
            'phone_number',
            'telephone_number',
            'economic_code',
            'national_code',
        ];

        public const SORTABLE = [
            'code',
            'customer_name',
            'created_at',
            'updated_at',
        ];

        protected $fillable = [
            'code',
            'customer_name',
            'type',
            'owner_name',
            'manager_name',
            'economic_code',
            'national_code',
            'phone_number',
            'telephone_number',
            'social_link',
            'birth_date',
            'status',
            'attachment',
            'description',
            'meta',
        ];

        protected function casts(): array {
            return array_merge(parent::casts(), [
                'type' => CustomerType::class,
                'status' => CustomerStatus::class,
                'birth_date' => 'date',
                'meta' => 'array',
            ]);
        }

        public function assignments(): HasMany {
            return $this->hasMany(CustomerAssignment::class);
        }

        public function addresses(): HasMany {
            return $this->hasMany(CustomerAddress::class);
        }

        public function deliveries(): HasMany {
            return $this->hasMany(Delivery::class);
        }

        public function defaultAddress(): HasOne {
            return $this->hasOne(CustomerAddress::class)->where('is_default', true);
        }

        public function scopeActive(Builder $query): Builder {
            return $query->where('status', CustomerStatus::ACTIVE->value);
        }

        public function scopeSearch(Builder $query, ?string $search): Builder {
            if (blank($search)) {
                return $query;
            }

            return $query->where(function (Builder $query) use ($search) {
                $query->where('code', 'like', "%{$search}%")->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('owner_name', 'like', "%{$search}%")->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('telephone_number', 'like', "%{$search}%")->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('social_link', 'like', "%{$search}%");
            });
        }

        public function scopeDefaultSort(Builder $query): Builder {
            return $query->orderBy('customer_name');
        }

        public function isActive(): bool {
            return $this->status === CustomerStatus::ACTIVE;
        }

        public static function codeGenerator(): CodeGeneratorData {
            return new CodeGeneratorData(sequence_key: 'customer', prefix: 'CUS', padding: 6);
        }
    }
