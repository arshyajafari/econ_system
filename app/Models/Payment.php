<?php

    namespace App\Models;

    use App\Enums\PaymentMethod;
    use App\Enums\PaymentStatus;
    use App\Traits\HasAudit;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class Payment extends BaseModel {
        use HasPublicId, HasAudit, SoftDeletes;

        public const DEFAULT_RELATIONS = [
            'invoice',
            'customer',
            'employee',
        ];

        public const SEARCHABLE = [
            'reference_number',
            'description',
        ];

        public const SORTABLE = [
            'payment_date',
            'amount',
            'created_at',
        ];

        protected $fillable = [
            'invoice_id',
            'customer_id',
            'employee_id',
            'status',
            'method',
            'amount',
            'reference_number',
            'payment_date',
            'description',
            'meta',
        ];

        protected $casts = [
            'status' => PaymentStatus::class,
            'method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'meta' => 'array',
        ];

        public function invoice(): BelongsTo {
            return $this->belongsTo(Invoice::class);
        }

        public function customer(): BelongsTo {
            return $this->belongsTo(Customer::class);
        }

        public function employee(): BelongsTo {
            return $this->belongsTo(Employee::class);
        }
    }
