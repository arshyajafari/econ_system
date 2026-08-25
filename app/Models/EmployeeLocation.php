<?php

    namespace App\Models;

    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;

    class EmployeeLocation extends BaseModel {
        use HasPublicId;

        public const DEFAULT_RELATIONS = [
            'employee',
        ];

        protected $fillable = [
            'employee_id',
            'latitude',
            'longitude',
            'accuracy',
            'source',
            'captured_at',
            'meta',
        ];

        protected $casts = [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy' => 'decimal:2',
            'captured_at' => 'datetime',
            'meta' => 'array',
        ];

        public function employee(): BelongsTo {
            return $this->belongsTo(Employee::class);
        }
    }
