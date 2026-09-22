<?php

namespace App\Models;

use App\Enums\OrderReturnStatus;
use App\Services\CodeGeneratorData;
use App\Traits\HasAudit;
use App\Traits\HasCodeGenerator;
use App\Traits\HasPublicId;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderReturn extends BaseModel
{
    use HasPublicId, HasAudit, HasCodeGenerator, SoftDeletes;

    public const array DEFAULT_RELATIONS = ['order', 'customer', 'employee', 'items.product'];
    public const array SEARCHABLE = ['code', 'description'];
    public const array SORTABLE = ['completed_at', 'delivered_at', 'created_at'];

    protected $fillable = ['code', 'order_id', 'customer_id', 'employee_id', 'status', 'completed_at', 'delivered_at', 'description', 'meta'];

    protected $casts = [
        'status' => OrderReturnStatus::class,
        'completed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'meta' => 'array',
    ];

    public function markAsReceived(?CarbonInterface $receivedAt = null): static
    {
        $this->delivered_at = $receivedAt ?? now();
        return $this;
    }

    public function hasBeenReceived(): bool
    {
        return $this->delivered_at !== null;
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function items(): HasMany { return $this->hasMany(OrderReturnItem::class); }

    public static function codeGenerator(): CodeGeneratorData {
        return new CodeGeneratorData(sequence_key: 'order_return', prefix: 'RET', padding: 6);
    }
}
