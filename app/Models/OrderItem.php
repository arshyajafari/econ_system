<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends BaseModel
{
    use HasPublicId, SoftDeletes;

    public const DEFAULT_RELATIONS = ['product.currentPrice', 'allocations.inventoryBatch'];

    public const DISCOUNT_TYPE_NONE = 'none';
    public const DISCOUNT_TYPE_PERCENTAGE = 'percentage';
    public const DISCOUNT_TYPE_FIXED = 'fixed';
    public const DISCOUNT_TYPES = [self::DISCOUNT_TYPE_NONE, self::DISCOUNT_TYPE_PERCENTAGE, self::DISCOUNT_TYPE_FIXED];

    public const OFFER_TYPE_NONE = 'none';
    public const OFFER_TYPE_BUY_X_GET_Y = 'buy_x_get_y';
    public const OFFER_TYPES = [self::OFFER_TYPE_NONE, self::OFFER_TYPE_BUY_X_GET_Y];

    protected $fillable = [
        'order_id','product_id','quantity','unit_price','total_price','description',
        'discount_type','discount_value','discount_amount','offer_type',
        'offer_buy_quantity','offer_free_quantity','offer_title',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'offer_buy_quantity' => 'integer',
        'offer_free_quantity' => 'integer',
    ];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
    public function allocations(): HasMany { return $this->hasMany(OrderItemAllocation::class); }
    public function returnItems(): HasMany { return $this->hasMany(OrderReturnItem::class); }

    public function calculatedDiscountAmount(): float {
        $subtotal = round((float) $this->quantity * (float) $this->unit_price, 2);
        if ($this->discount_type === self::DISCOUNT_TYPE_PERCENTAGE) {
            return min($subtotal, round($subtotal * ((float) $this->discount_value / 100), 2));
        }
        if ($this->discount_type === self::DISCOUNT_TYPE_FIXED) {
            return min($subtotal, round((float) $this->discount_value, 2));
        }
        return 0.0;
    }

    public function fulfillmentQuantity(): int {
        return (int) $this->quantity + (int) $this->offer_free_quantity;
    }

    public function isOfferValid(): bool {
        if ($this->offer_type !== self::OFFER_TYPE_BUY_X_GET_Y) return true;
        return (int) $this->offer_buy_quantity > 0 && (int) $this->offer_free_quantity > 0;
    }
}
