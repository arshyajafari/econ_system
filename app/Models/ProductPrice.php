<?php

namespace App\Models;

use App\Traits\HasAudit;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductPrice extends BaseModel
{
    use HasPublicId, SoftDeletes, HasAudit;

    public const DEFAULT_RELATIONS = ['product'];

    protected $fillable = [
        'product_id',
        'sale_price',
        'effective_from',
        'effective_to',
        'is_active',
        'description',
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
