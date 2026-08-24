<?php

    namespace App\Models;

    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class InventoryBatch extends BaseModel {
        use HasPublicId, SoftDeletes;

        public const DEFAULT_RELATIONS = [
            'product',
        ];

        public const SEARCHABLE = [
            'batch_number',
        ];

        public const SORTABLE = [
            'expire_date',
            'received_at',
            'created_at',
        ];

        protected $fillable = [
            'product_id',
            'batch_number',
            'expire_date',
            'received_at',
            'quantity',
            'reserved_quantity',
            'description',
        ];

        protected $casts = [
            'expire_date' => 'date',
            'received_at' => 'datetime',
            'quantity' => 'integer',
            'reserved_quantity' => 'integer',
        ];

        public function product(): BelongsTo {
            return $this->belongsTo(Product::class);
        }

        public function movements(): HasMany {
            return $this->hasMany(InventoryMovement::class);
        }

        public function adjustments(): HasMany {
            return $this->hasMany(InventoryAdjustment::class);
        }

        public function allocations(): HasMany {
            return $this->hasMany(OrderItemAllocation::class);
        }

        public function returnAllocations(): HasMany {
            return $this->hasMany(OrderReturnItemAllocation::class);
        }

        public function getAvailableQuantityAttribute(): int {
            return max(0, $this->quantity - $this->reserved_quantity);
        }

        public function getIsExpiredAttribute(): bool {
            return $this->expire_date !== null && $this->expire_date->isBefore(today());
        }

        public function getIsNearExpireAttribute(): bool {
            if (!$this->expire_date || $this->is_expired) {
                return false;
            }

            return today()->addDays(90)->greaterThanOrEqualTo($this->expire_date);
        }
    }
