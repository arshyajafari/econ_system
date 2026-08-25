<?php

    namespace App\Models;

    use App\Contracts\HasGeneratedCode;
    use App\Enums\EmployeeStatus;
    use App\Enums\EmploymentType;
    use App\Enums\Gender;
    use App\Services\CodeGeneratorData;
    use App\Traits\HasAudit;
    use App\Traits\HasCodeGenerator;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Database\Eloquent\Casts\Attribute;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Database\Eloquent\Relations\HasOne;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class Employee extends BaseModel implements HasGeneratedCode {
        use HasPublicId, HasFactory, HasCodeGenerator, SoftDeletes, HasAudit;

        public const DEFAULT_RELATIONS = [
            'defaultAddress',
            'user',
        ];

        public const SEARCHABLE = [
            'code',
            'first_name',
            'last_name',
            'phone_number',
            'national_code',
        ];

        public const SORTABLE = [
            'code',
            'first_name',
            'last_name',
            'hire_date',
            'created_at',
        ];

        protected $fillable = [
            'code',
            'first_name',
            'last_name',
            'national_code',
            'phone_number',
            'social_link',
            'email',
            'gender',
            'birth_date',
            'card_number',
            'iban_number',
            'employment_type',
            'hire_date',
            'termination_date',
            'status',
            'description',
            'meta',
        ];

        protected function casts(): array {
            return array_merge(parent::casts(), [
                'gender' => Gender::class,
                'status' => EmployeeStatus::class,
                'employment_type' => EmploymentType::class,
                'birth_date' => 'date',
                'hire_date' => 'date',
                'termination_date' => 'date',
                'meta' => 'array',
            ]);
        }

        public function user(): HasOne {
            return $this->hasOne(User::class);
        }

        public function addresses(): HasMany {
            return $this->hasMany(EmployeeAddress::class);
        }

        public function locations(): HasMany {
            return $this->hasMany(EmployeeLocation::class);
        }

        public function deliveries(): HasMany {
            return $this->hasMany(Delivery::class);
        }

        public function defaultAddress(): HasOne {
            return $this->hasOne(EmployeeAddress::class)->where('is_default', true);
        }

        public function customerAssignments(): HasMany {
            return $this->hasMany(CustomerAssignment::class);
        }

        public function scopeActive(Builder $query): Builder {
            return $query->where('status', EmployeeStatus::ACTIVE->value);
        }

        public function scopeInactive(Builder $query): Builder {
            return $query->where('status', EmployeeStatus::INACTIVE->value);
        }

        public function scopeSearch(Builder $query, ?string $search): Builder {
            if (!$search) {
                return $query;
            }

            return $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('national_code', 'like', "%{$search}%");
            });
        }

        public function scopeDefaultSort(Builder $query): Builder {
            return $query->orderBy('last_name')->orderBy('first_name');
        }

        protected function fullName(): Attribute {
            return Attribute::make(get: fn() => trim("{$this->first_name} {$this->last_name}"));
        }

        public static function codeGenerator(): CodeGeneratorData {
            return new CodeGeneratorData(sequence_key: 'employee', prefix: 'EMP', padding: 6);
        }
    }
