<?php

    namespace App\Models;

    use App\Enums\OrderStatus;
    use App\Services\CodeGeneratorData;
    use App\Traits\HasAudit;
    use App\Traits\HasCodeGenerator;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Database\Eloquent\Relations\HasOne;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class Order extends BaseModel {
        use HasPublicId, HasAudit, HasCodeGenerator, SoftDeletes;

        public const DEFAULT_RELATIONS = [
            'customer',
            'salesEmployee',
            'items.product',
            'items.allocations.inventoryBatch.product',
        ];

        public const SEARCHABLE = [
            'description',
        ];

        public const SORTABLE = [
            'ordered_at',
            'created_at',
        ];

        protected $fillable = [
            'code',
            'customer_id',
            'sales_employee_id',
            'status',
            'ordered_at',
            'description',
            'meta',
        ];

        protected $casts = [
            'status' => OrderStatus::class,
            'ordered_at' => 'datetime',
            'meta' => 'array',
        ];

        public function customer(): BelongsTo {
            return $this->belongsTo(Customer::class);
        }

        public function salesEmployee(): BelongsTo {
            return $this->belongsTo(Employee::class, 'sales_employee_id');
        }

        public function items(): HasMany {
            return $this->hasMany(OrderItem::class);
        }

        public function returns(): HasMany {
            return $this->hasMany(OrderReturn::class);
        }

        public function invoice(): HasOne {
            return $this->hasOne(Invoice::class);
        }

        public function delivery(): HasOne {
            return $this->hasOne(Delivery::class);
        }

        public static function codeGenerator(): CodeGeneratorData {
            return new CodeGeneratorData(sequence_key: 'order', prefix: 'ORD', padding: 6);
        }
    }
