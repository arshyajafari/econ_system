<?php

    namespace App\Models;

    use App\Enums\InventoryAdjustmentType;
    use App\Traits\HasAudit;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class InventoryAdjustment extends BaseModel {
        use HasAudit, HasPublicId, SoftDeletes;

        public const DEFAULT_RELATIONS = [
            'inventoryBatch.product',
        ];

        public const SEARCHABLE = [
            'reason',
        ];

        public const SORTABLE = [
            'created_at',
            'quantity',
        ];

        protected $fillable = [
            'inventory_batch_id',
            'type',
            'quantity',
            'reason',
            'description',
        ];

        protected $casts = [
            'type' => InventoryAdjustmentType::class,
        ];

        public function inventoryBatch(): BelongsTo {
            return $this->belongsTo(InventoryBatch::class);
        }
    }
