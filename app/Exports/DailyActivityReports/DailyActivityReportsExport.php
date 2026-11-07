<?php

namespace App\Exports\DailyActivityReports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DailyActivityReportsExport implements Export, WithMultipleSheets
{
    public function __construct(private readonly Builder $query)
    {
    }

    public function sheets(): array
    {
        return [
            new DailyActivityReportsDetailSheet(clone $this->query),
            new DailyActivityReportsSummarySheet(clone $this->query),
        ];
    }
}
