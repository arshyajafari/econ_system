<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Relations\BelongsTo;

    class CustomerAssignment extends BaseModel {
        protected $fillable = [
            'customer_id',
            'employee_id',
            'is_primary',
            'started_at',
            'ended_at',
            'is_active',
            'description',
        ];

        protected function casts(): array {
            return [
                'started_at' => 'date',
                'ended_at' => 'date',
                'is_primary' => 'boolean',
                'is_active' => 'boolean',
            ];
        }

        public function customer(): BelongsTo {
            return $this->belongsTo(Customer::class);
        }

        public function employee(): BelongsTo {
            return $this->belongsTo(Employee::class);
        }
    }
