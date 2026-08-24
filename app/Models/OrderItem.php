<?php

    namespace App\Models;

    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class OrderItem extends BaseModel {
        use HasPublicId, SoftDeletes;

        public const DEFAULT_RELATIONS = [
            'product',
            'allocations.inventoryBatch',
        ];

        protected $fillable = [
            'order_id',
            'product_id',
            'quantity',
            'unit_price',
            'total_price',
            'description',
        ];

        protected $casts = [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];

        public function order(): BelongsTo {
            return $this->belongsTo(Order::class);
        }

        public function product(): BelongsTo {
            return $this->belongsTo(Product::class);
        }

        public function allocations(): HasMany {
            return $this->hasMany(OrderItemAllocation::class);
        }

        public function returnItems(): HasMany {
            return $this->hasMany(OrderReturnItem::class);
        }
    }
