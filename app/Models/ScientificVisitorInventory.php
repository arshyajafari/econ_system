<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasAudit;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScientificVisitorInventory extends BaseModel
{
    use HasPublicId, SoftDeletes, HasAudit;

    public const DEFAULT_RELATIONS = ['employee', 'product.currentPrice'];

    protected $fillable = [
        'employee_id',
        'product_id',
        'received_quantity',
        'used_quantity',
        'last_received_at',
        'description',
    ];

    protected $casts = [
        'received_quantity' => 'integer',
        'used_quantity' => 'integer',
        'last_received_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->received_quantity - $this->used_quantity);
    }
}