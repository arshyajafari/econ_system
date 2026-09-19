<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCreditAllocation extends BaseModel {
    protected $fillable = [
        'customer_id',
        'source_transaction_id',
        'invoice_id',
        'amount',
        'allocated_at',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'allocated_at' => 'datetime',
    ];

    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }

    public function sourceTransaction(): BelongsTo {
        return $this->belongsTo(CustomerTransaction::class, 'source_transaction_id');
    }

    public function invoice(): BelongsTo {
        return $this->belongsTo(Invoice::class);
    }
}
