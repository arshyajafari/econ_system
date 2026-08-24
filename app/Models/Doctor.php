<?php

    namespace App\Models;

    use App\Contracts\HasGeneratedCode;
    use App\Enums\DoctorSpecialty;
    use App\Enums\DoctorStatus;
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

    class Doctor extends BaseModel implements HasGeneratedCode {
        use HasPublicId, HasFactory, HasCodeGenerator, SoftDeletes, HasAudit;

        public const DEFAULT_RELATIONS = [
            'defaultAddress',
        ];

        public const SEARCHABLE = [
            'code',
            'first_name',
            'last_name',
            'phone_number',
            'clinic_name',
        ];

        public const SORTABLE = [
            'code',
            'first_name',
            'last_name',
            'created_at',
            'updated_at',
        ];

        protected $fillable = [
            'first_name',
            'last_name',
            'specialty',
            'phone_number',
            'clinic_name',
            'attachment',
            'status',
            'is_favorite',
            'description',
            'meta',
        ];

        protected function casts(): array {
            return array_merge(parent::casts(), [
                'specialty' => DoctorSpecialty::class,
                'status' => DoctorStatus::class,
                'meta' => 'array',
                'is_favorite' => 'boolean',
            ]);
        }

        public function addresses(): HasMany {
            return $this->hasMany(DoctorAddress::class);
        }

        public function defaultAddress(): HasOne {
            return $this->hasOne(DoctorAddress::class)->where('is_default', true);
        }

        public function scopeActive(Builder $query): Builder {
            return $query->where('status', DoctorStatus::ACTIVE->value);
        }

        public function scopeSearch(Builder $query, ?string $search): Builder {
            if (blank($search)) {
                return $query;
            }

            return $query->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")->orWhere('specialty', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        public function scopeSpecialty(Builder $query, DoctorSpecialty $specialty): Builder {

            return $query->where('specialty', $specialty);
        }

        public function scopeDefaultSort(Builder $query): Builder {
            return $query->orderBy('last_name')->orderBy('first_name');
        }

        public function isActive(): bool {
            return $this->status === DoctorStatus::ACTIVE;
        }

        protected function fullName(): Attribute {
            return Attribute::make(get: fn() => trim("{$this->first_name} {$this->last_name}"));
        }

        public static function codeGenerator(): CodeGeneratorData {
            return new CodeGeneratorData(sequence_key: 'doctor', prefix: 'DOC', padding: 6);
        }
    }
