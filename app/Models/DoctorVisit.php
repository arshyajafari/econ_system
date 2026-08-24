<?php

    namespace App\Models;

    use App\Traits\HasAudit;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class DoctorVisit extends BaseModel {
        use HasPublicId, HasAudit, SoftDeletes;

        public const array DEFAULT_RELATIONS = [
            'doctor',
            'employee',
            'samples.product',
        ];

        public const SEARCHABLE = [
            'description',
        ];

        public const SORTABLE = [
            'visit_date',
            'created_at',
        ];

        protected $fillable = [
            'doctor_id',
            'employee_id',
            'visit_date',
            'description',
            'meta',
        ];

        protected $casts = [
            'visit_date' => 'date',
            'meta' => 'array',
        ];

        public function doctor(): BelongsTo {
            return $this->belongsTo(Doctor::class);
        }

        public function employee(): BelongsTo {
            return $this->belongsTo(Employee::class);
        }

        public function samples(): HasMany {
            return $this->hasMany(DoctorVisitSample::class);
        }
    }
