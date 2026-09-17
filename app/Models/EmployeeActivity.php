<?php

namespace App\Models;

use App\Enums\EmployeeActivityType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeActivity extends BaseModel {
    protected $fillable = ['employee_id', 'activity_type'];

    protected function casts(): array {
        return array_merge(parent::casts(), ['activity_type' => EmployeeActivityType::class]);
    }

    public function employee(): BelongsTo {
        return $this->belongsTo(Employee::class);
    }
}
