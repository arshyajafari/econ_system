<?php

namespace App\Exports\DailyActivityReports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DailyActivityReportsDetailSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private readonly Builder $query)
    {
    }

    public function collection()
    {
        return $this->query->orderBy('visit_date')->get()->map(fn ($row) => [
            $row->employee_name_snapshot,
            $row->visit_date?->toDateString(),
            $row->visit_time,
            $row->organization_name,
            $row->contact_name,
            $row->contact_type === 'phone' ? 'تلفنی' : 'حضوری',
            $row->purpose,
            $row->discussion,
            $row->employee_question,
            $row->audience_feedback,
            $row->outcome,
            match ($row->result) {
                'positive' => 'مثبت',
                'neutral' => 'خنثی',
                'negative' => 'منفی',
                'follow_up' => 'نیازمند پیگیری',
                default => $row->result,
            },
        ]);
    }

    public function headings(): array
    {
        return ['کارمند', 'تاریخ مراجعه', 'ساعت', 'مجموعه مورد مراجعه', 'مخاطب', 'نوع ارتباط', 'هدف مراجعه', 'شرح مذاکرات', 'سؤال کارمند', 'بازخورد مخاطب', 'نتیجه مراجعه', 'ارزیابی نتیجه'];
    }

    public function title(): string
    {
        return 'گزارش تفصیلی';
    }
}
