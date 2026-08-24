<?php

    namespace App\Models;

    use App\Traits\HasAudit;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class OrderReturnItemAllocation extends BaseModel {
        use HasPublicId, HasAudit, SoftDeletes;

        public const DEFAULT_RELATIONS = [
            'orderReturnItem',
            'inventoryBatch.product',
        ];

        protected $fillable = [
            'order_return_item_id',
            'inventory_batch_id',
            'quantity',
        ];

        protected $casts = [
            'quantity' => 'integer',
        ];

        public function orderReturnItem(): BelongsTo {
            return $this->belongsTo(OrderReturnItem::class);
        }

        public function inventoryBatch(): BelongsTo {
            return $this->belongsTo(InventoryBatch::class);
        }
    }
