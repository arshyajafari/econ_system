<?php

namespace App\Models;

use App\Enums\InventoryMovementType;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryBatch extends BaseModel
{
    use HasPublicId, SoftDeletes;

    public const DEFAULT_RELATIONS = ['product.currentPrice'];
    public const SEARCHABLE = ['batch_number'];
    public const SORTABLE = ['expire_date', 'received_at', 'created_at'];

    /**
     * Movements created only when stock is initially entered are not evidence
     * that a batch has been used operationally.
     */
    private const INITIAL_STOCK_MOVEMENT_REASONS = [
        'inventory_receipt',
        'inventory_initial_balance',
    ];

    protected $fillable = [
        'product_id', 'batch_number', 'expire_date', 'received_at', 'quantity',
        'purchase_price', 'reserved_quantity', 'description',
    ];

    protected $casts = [
        'expire_date' => 'date',
        'received_at' => 'datetime',
        'quantity' => 'integer',
        'purchase_price' => 'decimal:2',
        'reserved_quantity' => 'integer',
    ];

    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
    public function movements(): HasMany { return $this->hasMany(InventoryMovement::class); }
    public function adjustments(): HasMany { return $this->hasMany(InventoryAdjustment::class); }
    public function allocations(): HasMany { return $this->hasMany(OrderItemAllocation::class); }
    public function returnAllocations(): HasMany { return $this->hasMany(OrderReturnItemAllocation::class); }

    /**
     * A batch is deletable only when it has no operational history.
     *
     * Initial receipt / opening-balance movements are allowed so users can
     * remove an unused batch entered by mistake. Adjustments, reservations,
     * order allocations, return allocations and all non-initial movements
     * preserve the batch as part of the audit trail. Trashed related records
     * are deliberately included.
     */
    public function canBeDeleted(): bool
    {
        if ((int) $this->reserved_quantity > 0) {
            return false;
        }

        if ($this->adjustments()->withTrashed()->exists()) {
            return false;
        }

        if ($this->allocations()->withTrashed()->exists()) {
            return false;
        }

        if ($this->returnAllocations()->withTrashed()->exists()) {
            return false;
        }

        return !$this->movements()
            ->where(function ($query) {
                $query->whereNull('reason')
                    ->orWhereNotIn('reason', self::INITIAL_STOCK_MOVEMENT_REASONS);
            })
            ->exists();
    }

    public function getAvailableQuantityAttribute(): int { return max(0, $this->quantity - $this->reserved_quantity); }
    public function getIsExpiredAttribute(): bool { return $this->expire_date !== null && $this->expire_date->isBefore(today()); }

    public function getIsNearExpireAttribute(): bool
    {
        if (!$this->expire_date || $this->is_expired) return false;
        return today()->addDays(90)->greaterThanOrEqualTo($this->expire_date);
    }
}
