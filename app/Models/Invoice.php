<?php

namespace App\Models;

use App\Enums\CustomerTransactionType;
use App\Enums\InvoiceStatus;
use App\Enums\OrderReturnStatus;
use App\Enums\PaymentStatus;
use App\Services\CodeGeneratorData;
use App\Traits\HasAudit;
use App\Traits\HasCodeGenerator;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends BaseModel {
    use HasPublicId, HasAudit, HasCodeGenerator, SoftDeletes;

    public const DEFAULT_RELATIONS = [
        'order',
        'customer',
        'employee',
        'items.product',
        'payments',
        'returnTransactions.orderReturn',
        'creditAllocations',
    ];

    public const SEARCHABLE = [
        'code',
        'description',
    ];

    public const SORTABLE = [
        'issued_at',
        'due_date',
        'total_amount',
        'created_at',
    ];

    protected $fillable = [
        'code',
        'order_id',
        'customer_id',
        'employee_id',
        'status',
        'issued_at',
        'due_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'description',
        'meta',
    ];

    protected $casts = [
        'status' => InvoiceStatus::class,
        'issued_at' => 'datetime',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function order(): BelongsTo {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }

    public function employee(): BelongsTo {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany {
        return $this->hasMany(Payment::class);
    }

    public function creditAllocations(): HasMany {
        return $this->hasMany(CustomerCreditAllocation::class);
    }

    public function returnTransactions(): HasManyThrough {
        return $this->hasManyThrough(
            CustomerTransaction::class,
            OrderReturn::class,
            'order_id',
            'order_return_id',
            'order_id',
            'id',
        );
    }

    public function confirmedPaidAmount(): float {
        return $this->relationLoaded('payments')
            ? (float) $this->payments
                ->where('status', PaymentStatus::CONFIRMED)
                ->sum(fn ($payment) => (float) $payment->amount + (float) $payment->settlement_discount_amount)
            : 0.0;
    }

    public function pendingPaidAmount(): float {
        return $this->relationLoaded('payments')
            ? (float) $this->payments
                ->where('status', PaymentStatus::PENDING)
                ->sum(fn ($payment) => (float) $payment->amount + (float) $payment->settlement_discount_amount)
            : 0.0;
    }

    public function completedReturnCreditAmount(): float {
        return $this->relationLoaded('returnTransactions')
            ? (float) $this->returnTransactions
                ->where('type', CustomerTransactionType::CREDIT)
                ->filter(fn ($transaction) => $transaction->orderReturn?->status === OrderReturnStatus::COMPLETED)
                ->sum('amount')
            : 0.0;
    }

    /**
     * Return credit is normally allocated to its originating invoice when
     * the return is completed. This method covers both that normal path and
     * older data where the return credit exists but has no allocation yet.
     */
    public function unallocatedCompletedReturnCreditAmount(): float {
        $returnCredit = $this->relationLoaded('returnTransactions')
            ? $this->completedReturnCreditAmount()
            : (float) $this->returnTransactions()
                ->where('customer_transactions.type', CustomerTransactionType::CREDIT)
                ->where('order_returns.status', OrderReturnStatus::COMPLETED)
                ->sum('customer_transactions.amount');

        if ($returnCredit <= 0) {
            return 0.0;
        }

        $allocatedReturnCredit = (float) CustomerCreditAllocation::query()
            ->whereHas('sourceTransaction', function ($query) {
                $query
                    ->whereNotNull('order_return_id')
                    ->whereHas('orderReturn', fn ($return) => $return
                        ->where('order_id', $this->order_id)
                        ->where('status', OrderReturnStatus::COMPLETED));
            })
            ->sum('amount');

        return max(0.0, round($returnCredit - $allocatedReturnCredit, 2));
    }

    public function appliedCustomerCreditAmount(): float {
        return $this->relationLoaded('creditAllocations')
            ? (float) $this->creditAllocations->sum('amount')
            : (float) $this->creditAllocations()->sum('amount');
    }

    /**
     * Effective settlement:
     *   confirmed payments
     * + all explicit customer-credit allocations
     * + completed return credit that has not been allocated yet.
     *
     * A return allocation is therefore counted exactly once: either through
     * creditAllocations or as still-unallocated return credit.
     */
    public function settledAmount(): float {
        return $this->confirmedPaidAmount()
            + $this->appliedCustomerCreditAmount()
            + $this->unallocatedCompletedReturnCreditAmount();
    }

    public function effectiveRemainingAmount(bool $includePending = false): float {
        $remaining = (float) $this->total_amount - $this->settledAmount();

        if ($includePending) {
            $remaining -= $this->pendingPaidAmount();
        }

        return max(0.0, round($remaining, 2));
    }

    public function isSettled(): bool {
        return $this->effectiveRemainingAmount() <= 0.0;
    }

    public static function codeGenerator(): CodeGeneratorData {
        return new CodeGeneratorData(sequence_key: 'invoice', prefix: 'INV', padding: 6);
    }
}
