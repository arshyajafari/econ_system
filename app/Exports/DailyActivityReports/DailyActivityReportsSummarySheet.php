<?php

namespace App\Exports\DailyActivityReports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DailyActivityReportsSummarySheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private readonly Builder $query)
    {
    }

    public function collection()
    {
        return $this->query->get()->groupBy('employee_name_snapshot')->map(function ($rows, $employee) {
            return [
                $employee,
                $rows->count(),
                $rows->where('contact_type', 'in_person')->count(),
                $rows->where('contact_type', 'phone')->count(),
                $rows->where('result', 'positive')->count(),
                $rows->where('result', 'neutral')->count(),
                $rows->where('result', 'negative')->count(),
                $rows->where('result', 'follow_up')->count(),
            ];
        })->values();
    }

    public function headings(): array
    {
        return ['کارمند', 'تعداد گزارش', 'حضوری', 'تلفنی', 'مثبت', 'خنثی', 'منفی', 'نیازمند پیگیری'];
    }

    public function title(): string
    {
        return 'خلاصه عملکرد';
    }
}
