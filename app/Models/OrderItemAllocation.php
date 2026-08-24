<?php

    namespace App\Models;

    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class OrderItemAllocation extends BaseModel {
        use HasPublicId, SoftDeletes;

        public const DEFAULT_RELATIONS = [
            'orderItem',
            'inventoryBatch.product',
        ];

        protected $fillable = [
            'order_item_id',
            'inventory_batch_id',
            'quantity',
        ];

        protected $casts = [
            'quantity' => 'integer',
        ];

        public function orderItem(): BelongsTo {
            return $this->belongsTo(OrderItem::class);
        }

        public function inventoryBatch(): BelongsTo {
            return $this->belongsTo(InventoryBatch::class);
        }
    }
