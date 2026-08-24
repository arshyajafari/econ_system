<?php

    namespace App\Models;

    use App\Traits\HasAudit;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class DoctorAddress extends BaseModel {
        use HasFactory, SoftDeletes, HasAudit;

        protected $fillable = [
            'doctor_id',
            'province',
            'city',
            'address',
            'postal_code',
            'latitude',
            'longitude',
            'is_default',
        ];

        protected function casts(): array {
            return array_merge(parent::casts(), [
                'latitude' => 'decimal:7',
                'longitude' => 'decimal:7',
                'is_default' => 'boolean',
            ]);
        }

        public function doctor(): BelongsTo {
            return $this->belongsTo(Doctor::class);
        }

        public function scopeDefaultSort(Builder $query): Builder {
            return $query->orderByDesc('is_default')->orderBy('province')->orderBy('city');
        }
    }
