<?php

    namespace App\Models;

    use App\Enums\CustomerTransactionType;
    use App\Traits\HasAudit;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class CustomerTransaction extends BaseModel {
        use HasPublicId, HasAudit, SoftDeletes;

        public const DEFAULT_RELATIONS = [
            'customer',
            'invoice',
            'payment',
            'orderReturn',
        ];

        public const SEARCHABLE = [
            'description',
        ];

        public const SORTABLE = [
            'transaction_at',
            'amount',
            'created_at',
        ];

        protected $fillable = [
            'customer_id',
            'invoice_id',
            'payment_id',
            'order_return_id',
            'type',
            'amount',
            'transaction_at',
            'description',
            'meta',
        ];

        protected $casts = [
            'type' => CustomerTransactionType::class,
            'amount' => 'decimal:2',
            'transaction_at' => 'datetime',
            'meta' => 'array',
        ];

        public function customer(): BelongsTo {
            return $this->belongsTo(Customer::class);
        }

        public function invoice(): BelongsTo {
            return $this->belongsTo(Invoice::class);
        }

        public function payment(): BelongsTo {
            return $this->belongsTo(Payment::class);
        }

        public function orderReturn(): BelongsTo {
            return $this->belongsTo(OrderReturn::class);
        }
    }
