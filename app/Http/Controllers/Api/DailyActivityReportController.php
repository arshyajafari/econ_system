<?php

namespace App\Http\Controllers\Api;

use App\Exports\DailyActivityReports\DailyActivityReportsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\DailyActivityReportRequest;
use App\Http\Resources\DailyActivityReportResource;
use App\Models\DailyActivityReport;
use App\Support\JalaliDate;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class DailyActivityReportController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', DailyActivityReport::class);
        $query = $this->filteredQuery($request)->whereDate('retention_until', '>', now('Asia/Tehran')->toDateString());

        return DailyActivityReportResource::collection(
            $query->orderByDesc('visit_date')->orderByDesc('id')->paginate(min((int) $request->input('per_page', 20), 100))
        );
    }

    public function store(DailyActivityReportRequest $request)
    {
        $this->authorize('create', DailyActivityReport::class);
        $employee = $request->user()->employee;
        abort_unless($employee, 403, 'حساب کاربری به کارمند متصل نیست.');

        $data = $request->safe()->except(['edit_reason']);
        $data['employee_id'] = $employee->id;
        $data['employee_name_snapshot'] = trim($employee->first_name . ' ' . $employee->last_name);
        $data['created_by_user_id'] = $request->user()->id;
        $data['retention_until'] = JalaliDate::retentionDate($data['visit_date']);

        $report = DailyActivityReport::query()->create($data);

        return (new DailyActivityReportResource($report->load('employee')))->response()->setStatusCode(201);
    }

    public function show(Request $request, DailyActivityReport $dailyActivityReport)
    {
        $this->authorizeReportAccess($request, $dailyActivityReport, 'daily_activity_reports.view_own', 'daily_activity_reports.view_all');
        abort_if($dailyActivityReport->retention_until?->toDateString() <= now('Asia/Tehran')->toDateString(), 410, 'این گزارش به بخش پاک‌سازی منتقل شده است.');

        return new DailyActivityReportResource($dailyActivityReport->load(['employee', 'revisions']));
    }

    public function update(DailyActivityReportRequest $request, DailyActivityReport $dailyActivityReport)
    {
        $this->authorizeReportAccess($request, $dailyActivityReport, 'daily_activity_reports.update_own', 'daily_activity_reports.update_all');
        abort_if($dailyActivityReport->retention_until?->toDateString() <= now('Asia/Tehran')->toDateString(), 409, 'گزارش منقضی‌شده از بخش پاک‌سازی مدیریت می‌شود.');

        $data = $request->safe()->except(['edit_reason']);
        $data['retention_until'] = JalaliDate::retentionDate($data['visit_date']);
        $fields = array_keys($data);
        $old = $dailyActivityReport->only($fields);
        $new = $data;
        $changesOld = [];
        $changesNew = [];

        foreach ($fields as $field) {
            $before = $old[$field] ?? null;
            $after = $new[$field] ?? null;
            if ((string) ($before ?? '') !== (string) ($after ?? '')) {
                $changesOld[$field] = $before;
                $changesNew[$field] = $after;
            }
        }

        if ($changesNew !== []) {
            DB::transaction(function () use ($dailyActivityReport, $data, $request, $changesOld, $changesNew) {
                $dailyActivityReport->update($data);
                $dailyActivityReport->revisions()->create([
                    'user_id' => $request->user()->id,
                    'reason' => $request->validated('edit_reason'),
                    'old_values' => $changesOld,
                    'new_values' => $changesNew,
                    'created_at' => now(),
                ]);
            });
        }

        return new DailyActivityReportResource($dailyActivityReport->fresh()->load(['employee', 'revisions']));
    }

    public function export(Request $request)
    {
        $archive = $request->boolean('archive');
        if ($archive) {
            // Archive export is part of the explicit cleanup workflow.
            $this->authorize('cleanup', DailyActivityReport::class);
        } else {
            $this->authorize('export', DailyActivityReport::class);
        }

        $query = $this->filteredQuery(
            $request,
            $archive ? 'daily_activity_reports.cleanup' : 'daily_activity_reports.export_all',
        );
        $query->whereDate(
            'retention_until',
            $archive ? '<=' : '>',
            now('Asia/Tehran')->toDateString()
        );

        $filename = 'daily-activity-reports-' . now('Asia/Tehran')->format('Ymd-His') . '.xlsx';

        return Excel::download(new DailyActivityReportsExport($query), $filename);
    }

    public function archive(Request $request)
    {
        $this->authorize('cleanup', DailyActivityReport::class);
        $query = $this->filteredQuery($request, 'daily_activity_reports.cleanup')
            ->whereDate('retention_until', '<=', now('Asia/Tehran')->toDateString());

        return DailyActivityReportResource::collection(
            $query->orderBy('retention_until')->orderBy('visit_date')->paginate(min((int) $request->input('per_page', 20), 100))
        );
    }

    public function cleanupHistory(Request $request)
    {
        $this->authorize('cleanup', DailyActivityReport::class);

        return response()->json([
            'data' => DB::table('daily_activity_report_cleanup_runs')
                ->leftJoin('users', 'users.id', '=', 'daily_activity_report_cleanup_runs.user_id')
                ->select('daily_activity_report_cleanup_runs.*', 'users.login as performed_by')
                ->orderByDesc('daily_activity_report_cleanup_runs.id')
                ->limit(30)->get()
                ->map(function ($row) {
                    $row->report_ids = json_decode($row->report_ids, true) ?? [];
                    return $row;
                }),
        ]);
    }

    public function cleanup(Request $request)
    {
        $this->authorize('cleanup', DailyActivityReport::class);
        $data = $request->validate([
            'report_ids' => ['required', 'array', 'min:1'],
            'report_ids.*' => ['required', 'integer', 'distinct', 'exists:daily_activity_reports,id'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'confirmation' => ['required', 'in:DELETE'],
        ]);

        return DB::transaction(function () use ($request, $data) {
            $reports = DailyActivityReport::query()
                ->whereIn('id', $data['report_ids'])
                ->whereDate('retention_until', '<=', now('Asia/Tehran')->toDateString())
                ->lockForUpdate()
                ->get();

            if ($reports->count() !== count($data['report_ids'])) {
                throw ValidationException::withMessages([
                    'report_ids' => ['فقط گزارش‌های منتقل‌شده به بخش پاک‌سازی قابل حذف هستند. صفحه را تازه‌سازی کنید.'],
                ]);
            }

            $ids = $reports->modelKeys();
            $count = $reports->count();
            DailyActivityReport::query()->whereIn('id', $ids)->delete();

            DB::table('daily_activity_report_cleanup_runs')->insert([
                'user_id' => $request->user()->id,
                'report_ids' => json_encode($ids),
                'deleted_count' => $count,
                'reason' => $data['reason'],
                'created_at' => now(),
            ]);

            return response()->json([
                'message' => 'گزارش‌های انتخاب‌شده با موفقیت پاک‌سازی شدند.',
                'data' => ['deleted_count' => $count],
            ]);
        });
    }

    private function authorizeReportAccess(Request $request, DailyActivityReport $report, string $ownPermission, string $allPermission): void
    {
        $user = $request->user();
        $canManageAll = $user->hasRole('admin') || $user->can($allPermission);
        $isOwner = $user->employee_id !== null
            && (int) $user->employee_id === (int) $report->employee_id;

        abort_unless($canManageAll || ($isOwner && $user->can($ownPermission)), 403);
    }

    private function filteredQuery(Request $request, string $allPermission = 'daily_activity_reports.view_all'): Builder
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'contact_type' => ['nullable', 'in:in_person,phone'],
            'result' => ['nullable', 'in:positive,neutral,negative,follow_up'],
            'search' => ['nullable', 'string', 'max:200'],
            'employee_id' => ['nullable', 'string', 'max:64'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();
        $query = DailyActivityReport::query()->with('employee');

        if (!$user->can($allPermission)) {
            abort_unless($user->employee_id, 403);
            $query->where('employee_id', $user->employee_id);
        } elseif ($request->filled('employee_id')) {
            $query->whereHas('employee', fn (Builder $employee) => $employee->where('public_id', $request->string('employee_id')->toString()));
        }

        if ($request->filled('from')) {
            $query->whereDate('visit_date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('visit_date', '<=', $request->input('to'));
        }
        if ($request->filled('contact_type')) {
            $query->where('contact_type', $request->string('contact_type')->toString());
        }
        if ($request->filled('result')) {
            $query->where('result', $request->string('result')->toString());
        }
        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('employee_name_snapshot', 'like', "%{$search}%")
                    ->orWhere('organization_name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhere('discussion', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
