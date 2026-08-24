<?php

    namespace App\Models;

    use App\Traits\HasAudit;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class OrderReturnItem extends BaseModel {
        use HasPublicId, HasAudit, SoftDeletes;

        protected $fillable = [
            'order_return_id',
            'order_item_id',
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

        public function orderReturn(): BelongsTo {
            return $this->belongsTo(OrderReturn::class);
        }

        public function orderItem(): BelongsTo {
            return $this->belongsTo(OrderItem::class);
        }

        public function product(): BelongsTo {
            return $this->belongsTo(Product::class);
        }

        public function allocations(): HasMany {
            return $this->hasMany(OrderReturnItemAllocation::class);
        }
    }
