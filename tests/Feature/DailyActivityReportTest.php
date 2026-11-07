<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DailyActivityReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_create_and_edit_own_activity_report_and_edit_is_audited(): void
    {
        [$employee, $user, $token] = $this->createEmployeeUser('scientific visitor');

        $payload = [
            'visit_date' => '2026-11-07',
            'visit_time' => '10:30',
            'organization_name' => 'داروخانه نمونه',
            'contact_name' => 'مسئول فنی',
            'contact_type' => 'in_person',
            'purpose' => 'معرفی محصول',
            'discussion' => 'شرح مذاکره اولیه',
            'employee_question' => 'آیا موجودی کافی است؟',
            'audience_feedback' => 'نیاز به بررسی دارد',
            'outcome' => 'پیگیری هفته آینده',
            'result' => 'follow_up',
        ];

        $created = $this->withToken($token)
            ->postJson('/api/v1/daily-activity-reports', $payload)
            ->assertCreated()
            ->assertJsonPath('result', 'follow_up');

        $id = DB::table('daily_activity_reports')->value('id');

        $this->withToken($token)
            ->putJson('/api/v1/daily-activity-reports/' . $id, [
                ...$payload,
                'discussion' => 'شرح مذاکره اصلاح‌شده',
                'edit_reason' => 'اصلاح متن مذاکره',
            ])
            ->assertOk()
            ->assertJsonPath('discussion', 'شرح مذاکره اصلاح‌شده');

        $this->assertDatabaseHas('daily_activity_report_revisions', [
            'report_id' => $id,
            'user_id' => $user->id,
            'reason' => 'اصلاح متن مذاکره',
        ]);
        $this->assertDatabaseHas('daily_activity_reports', [
            'id' => $id,
            'employee_id' => $employee,
            'employee_name_snapshot' => 'Test Employee',
            'retention_until' => '2026-11-24',
        ]);
    }

    public function test_employee_cannot_edit_another_employees_report(): void
    {
        [, , $ownerToken] = $this->createEmployeeUser('sales visitor', 'Owner');
        [$otherEmployeeId, $otherUser, $otherToken] = $this->createEmployeeUser('sales visitor', 'Other');

        $payload = [
            'visit_date' => '2026-11-07',
            'organization_name' => 'مجموعه تست',
            'contact_type' => 'phone',
            'purpose' => 'پیگیری',
            'discussion' => 'مذاکره',
            'outcome' => 'تماس مجدد',
            'result' => 'neutral',
        ];

        $this->assertFalse($otherUser->can('daily_activity_reports.update_all'));
        $this->assertTrue($otherUser->can('daily_activity_reports.update_own'));
        $created = $this->withToken($ownerToken)
            ->postJson('/api/v1/daily-activity-reports', $payload)
            ->assertCreated();
        $id = DB::table('daily_activity_reports')->value('id');

        Auth::forgetGuards();

        $this->withToken($otherToken)
            ->putJson('/api/v1/daily-activity-reports/' . $id, [
                ...$payload,
                'edit_reason' => 'تلاش برای ویرایش گزارش دیگران',
            ])
            ->assertForbidden();
    }

    private function createEmployeeUser(string $role, string $suffix = ''): array
    {
        Role::findOrCreate($role, 'web');
        $now = now();
        $employeeId = DB::table('employees')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'EMP-' . Str::upper(Str::random(8)),
            'first_name' => $suffix === '' ? 'Test' : $suffix,
            'last_name' => 'Employee',
            'national_code' => Str::upper(Str::random(10)),
            'phone_number' => '09' . random_int(100000000, 999999999),
            'hire_date' => $now->toDateString(),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $user = User::query()->create([
            'employee_id' => $employeeId,
            'login' => 'daily-report-' . Str::lower(Str::random(12)),
            'password' => 'test-password',
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return [$employeeId, $user, $user->createToken('daily-report-test')->plainTextToken];
    }
}
