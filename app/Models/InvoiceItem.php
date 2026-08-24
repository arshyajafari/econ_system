<?php

    namespace App\Models;

    use App\Traits\HasAudit;
    use App\Traits\HasPublicId;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class InvoiceItem extends BaseModel {
        use HasPublicId, HasAudit, SoftDeletes;

        public const DEFAULT_RELATIONS = [
            'invoice',
            'orderItem',
            'product',
        ];

        protected $fillable = [
            'invoice_id',
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

        public function invoice(): BelongsTo {
            return $this->belongsTo(Invoice::class);
        }

        public function orderItem(): BelongsTo {
            return $this->belongsTo(OrderItem::class);
        }

        public function product(): BelongsTo {
            return $this->belongsTo(Product::class);
        }
    }
