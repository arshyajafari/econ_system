<?php

    namespace App\Models;

    use App\Enums\DeliveryStatus;
    use App\Traits\HasAudit;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class Delivery extends BaseModel {
        use HasPublicId, HasAudit, SoftDeletes;

        public const array DEFAULT_RELATIONS = [
            'order',
            'customer',
            'employee',
        ];

        public const SEARCHABLE = [
            'recipient_name',
            'description',
        ];

        public const SORTABLE = [
            'created_at',
            'prepared_at',
            'shipped_at',
            'delivered_at',
        ];

        protected $fillable = [
            'order_id',
            'customer_id',
            'employee_id',
            'status',
            'prepared_at',
            'shipped_at',
            'delivered_at',
            'cancelled_at',
            'recipient_name',
            'recipient_phone',
            'address',
            'description',
            'meta',
        ];

        protected $casts = [
            'status' => DeliveryStatus::class,
            'prepared_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'meta' => 'array',
        ];

        public function order(): BelongsTo {
            return $this->belongsTo(Order::class);
        }

        public function customer(): BelongsTo {
            return $this->belongsTo(Customer::class);
        }

        public function employee(): BelongsTo {
            return $this->belongsTo(Employee::class);
        }
    }
