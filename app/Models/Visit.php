<?php

    namespace App\Models;

    use App\Enums\VisitStatus;
    use App\Services\CodeGeneratorData;
    use App\Traits\HasAudit;
    use App\Traits\HasCodeGenerator;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class Visit extends BaseModel {
        use HasPublicId, HasAudit, HasCodeGenerator, SoftDeletes;

        public const DEFAULT_RELATIONS = [
            'doctor',
            'employee',
            'samples.product',
        ];

        public const SEARCHABLE = [
            'purpose',
            'description',
        ];

        public const SORTABLE = [
            'visit_date',
            'created_at',
        ];

        protected $fillable = [
            'client_operation_id',
            'doctor_id',
            'employee_id',
            'status',
            'visit_date',
            'latitude',
            'longitude',
            'location_accuracy',
            'location_captured_at',
            'purpose',
            'description',
            'meta',
        ];

        protected $casts = [
            'status' => VisitStatus::class,
            'visit_date' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'location_accuracy' => 'decimal:2',
            'location_captured_at' => 'datetime',
            'meta' => 'array',
        ];

        public function doctor(): BelongsTo {
            return $this->belongsTo(Doctor::class);
        }

        public function employee(): BelongsTo {
            return $this->belongsTo(Employee::class);
        }

        public function samples(): HasMany {
            return $this->hasMany(Sample::class);
        }

        public static function codeGenerator(): CodeGeneratorData {
            return new CodeGeneratorData(sequence_key: 'visit', prefix: 'VIS', padding: 6,);
        }
    }
