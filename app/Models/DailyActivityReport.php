<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyActivityReport extends BaseModel
{
    protected $fillable = [
        'employee_id', 'employee_name_snapshot', 'created_by_user_id',
        'visit_date', 'visit_time', 'organization_name', 'contact_name',
        'contact_type', 'purpose', 'discussion', 'employee_question',
        'audience_feedback', 'outcome', 'result', 'retention_until',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'visit_date' => 'date',
            'retention_until' => 'date',
        ]);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(DailyActivityReportRevision::class, 'report_id')->latest();
    }
}
