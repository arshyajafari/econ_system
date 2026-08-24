<?php

    namespace App\Models;

    use App\Enums\InvoiceStatus;
    use App\Services\CodeGeneratorData;
    use App\Traits\HasAudit;
    use App\Traits\HasCodeGenerator;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class Invoice extends BaseModel {
        use HasPublicId, HasAudit, HasCodeGenerator, SoftDeletes;

        public const DEFAULT_RELATIONS = [
            'order',
            'customer',
            'employee',
            'items.product',
            'payments',
        ];

        public const SEARCHABLE = [
            'code',
            'description',
        ];

        public const SORTABLE = [
            'issued_at',
            'due_date',
            'total_amount',
            'created_at',
        ];

        protected $fillable = [
            'code',
            'order_id',
            'customer_id',
            'employee_id',
            'status',
            'issued_at',
            'due_date',
            'subtotal',
            'discount_amount',
            'tax_amount',
            'total_amount',
            'description',
            'meta',
        ];

        protected $casts = [
            'status' => InvoiceStatus::class,
            'issued_at' => 'datetime',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
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

        public function items(): HasMany {
            return $this->hasMany(InvoiceItem::class);
        }

        public function payments(): HasMany {
            return $this->hasMany(Payment::class);
        }

        public static function codeGenerator(): CodeGeneratorData {
            return new CodeGeneratorData(sequence_key: 'invoice', prefix: 'INV', padding: 6);
        }
    }
