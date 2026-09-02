<?php

    namespace App\Models;

    use App\Enums\InventoryMovementType;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;

    class InventoryMovement extends BaseModel {
        use HasPublicId;

        public const DEFAULT_RELATIONS = [
            'inventoryBatch.product',
        ];

        public const SEARCHABLE = [
            'reason',
        ];

        public const SORTABLE = [
            'moved_at',
            'quantity',
            'created_at',
        ];

        protected $fillable = [
            'inventory_batch_id',
            'type',
            'quantity',
            'reason',
            'description',
            'moved_at',
        ];

        protected $casts = [
            'type' => InventoryMovementType::class,
            'moved_at' => 'datetime',
        ];

        public function inventoryBatch(): BelongsTo {
            return $this->belongsTo(InventoryBatch::class);
        }
    }
