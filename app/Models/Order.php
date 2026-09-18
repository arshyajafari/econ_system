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
        'offer_title',
        'offer_description',
    ];

    public const SORTABLE = [
        'ordered_at',
        'created_at',
    ];

    public const DISCOUNT_TYPE_NONE = 'none';
    public const DISCOUNT_TYPE_PERCENTAGE = 'percentage';
    public const DISCOUNT_TYPE_FIXED = 'fixed';

    public const DISCOUNT_TYPES = [
        self::DISCOUNT_TYPE_NONE,
        self::DISCOUNT_TYPE_PERCENTAGE,
        self::DISCOUNT_TYPE_FIXED,
    ];

    protected $fillable = [
        'code',
        'customer_id',
        'sales_employee_id',
        'status',
        'ordered_at',
        'description',
        'discount_type',
        'discount_value',
        'discount_amount',
        'offer_title',
        'offer_description',
        'meta',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'ordered_at' => 'datetime',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
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

    public function itemsSubtotal(): float {
        return round(
            $this->relationLoaded('items')
                ? (float) $this->items->sum(fn ($item) => (float) $item->total_price)
                : (float) $this->items()->sum('total_price'),
            2,
        );
    }

    public function calculatedDiscountAmount(?float $subtotal = null): float {
        $subtotal ??= $this->itemsSubtotal();

        if ($this->discount_type === self::DISCOUNT_TYPE_PERCENTAGE) {
            return min($subtotal, round($subtotal * ((float) $this->discount_value / 100), 2));
        }

        if ($this->discount_type === self::DISCOUNT_TYPE_FIXED) {
            return min($subtotal, round((float) $this->discount_value, 2));
        }

        return 0.0;
    }

    public function finalAmount(): float {
        return max(0.0, round($this->itemsSubtotal() - $this->calculatedDiscountAmount(), 2));
    }

    public static function codeGenerator(): CodeGeneratorData {
        return new CodeGeneratorData(sequence_key: 'order', prefix: 'ORD', padding: 6);
    }
}
