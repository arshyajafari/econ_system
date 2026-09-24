<?php

namespace App\Models;

use App\Traits\HasAudit;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends BaseModel
{
    use HasPublicId, HasAudit, SoftDeletes;

    public const DEFAULT_RELATIONS = [
        'employee',
        'creator.employee',
    ];

    public const SEARCHABLE = [
        'title',
        'description',
        'category',
    ];

    public const SORTABLE = [
        'expense_date',
        'amount',
        'created_at',
        'title',
    ];

    protected $fillable = [
        'title',
        'amount',
        'category',
        'expense_date',
        'employee_id',
        'created_by',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
