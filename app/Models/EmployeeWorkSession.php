<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeWorkSession extends BaseModel
{
    use HasPublicId;

    protected $fillable = [
        'employee_id',
        'work_date',
        'description',
        'started_at',
        'start_latitude',
        'start_longitude',
        'start_accuracy',
        'start_operation_id',
        'ended_at',
        'end_latitude',
        'end_longitude',
        'end_accuracy',
        'end_operation_id',
        'admin_correction_reason',
        'admin_corrected_by',
        'admin_corrected_at',
    ];

    protected $casts = [
        'work_date' => 'date',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'admin_corrected_at' => 'datetime',
        'start_latitude' => 'decimal:7',
        'start_longitude' => 'decimal:7',
        'start_accuracy' => 'decimal:2',
        'end_latitude' => 'decimal:7',
        'end_longitude' => 'decimal:7',
        'end_accuracy' => 'decimal:2',
    ];

    public function adminCorrectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_corrected_by');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(EmployeeLocation::class, 'work_session_id');
    }
}
