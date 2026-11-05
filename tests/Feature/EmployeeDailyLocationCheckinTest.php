<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeDailyLocationCheckinTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_record_only_one_daily_location_and_server_sets_the_timestamp(): void
    {
        $user = $this->createEmployeeUser();
        $this->actingAs($user);

        $this->getJson('/api/v1/employees/locations/check-ins/me')
            ->assertOk()
            ->assertJsonPath('data', null);

        $payload = [
            'latitude' => 36.5658,
            'longitude' => 53.0586,
            'accuracy' => 18.5,
            'client_operation_id' => '6d3d3f3e-77d9-4f4b-a0d5-9b2a2a3f1001',
        ];

        $saved = $this->postJson('/api/v1/employees/locations/check-ins/me', [
            ...$payload,
            'captured_at' => '2000-01-01T00:00:00Z',
        ]);
        $saved->assertCreated()->assertJsonPath('data.source', 'daily_checkin');

        $replayed = $this->postJson('/api/v1/employees/locations/check-ins/me', $payload);
        $replayed->assertOk()->assertJsonPath('data.id', $saved->json('data.id'));

        $secondAttempt = $this->postJson('/api/v1/employees/locations/check-ins/me', [
            ...$payload,
            'client_operation_id' => '6d3d3f3e-77d9-4f4b-a0d5-9b2a2a3f1002',
            'latitude' => 36.5700,
            'longitude' => 53.0600,
        ]);
        $secondAttempt->assertOk()->assertJsonPath('data.id', $saved->json('data.id'));

        $this->assertDatabaseCount('employee_locations', 1);
        $stored = DB::table('employee_locations')->first();
        $this->assertSame('daily_checkin', $stored->source);
        $this->assertNull($stored->work_session_id);
        $this->assertTrue(
            \Illuminate\Support\Carbon::parse($stored->captured_at)->greaterThan(now()->subMinute())
        );
        $this->assertNotSame('2000-01-01 00:00:00', $stored->captured_at);
    }

    public function test_admin_can_list_daily_checkins_but_regular_employee_cannot(): void
    {
        $user = $this->createEmployeeUser();
        $this->actingAs($user)->postJson('/api/v1/employees/locations/check-ins/me', [
            'latitude' => 36.5658,
            'longitude' => 53.0586,
            'accuracy' => 12,
            'client_operation_id' => '6d3d3f3e-77d9-4f4b-a0d5-9b2a2a3f1003',
        ])->assertCreated();

        $this->getJson('/api/v1/employees/locations/check-ins')
            ->assertForbidden();

        Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole('admin');

        $this->getJson('/api/v1/employees/locations/check-ins')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.source', 'daily_checkin');
    }

    private function createEmployeeUser(): User
    {
        $now = now();
        $employeeId = DB::table('employees')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'EMP-' . Str::upper(Str::random(8)),
            'first_name' => 'Daily',
            'last_name' => 'GPS',
            'national_code' => Str::upper(Str::random(10)),
            'phone_number' => '09' . random_int(100000000, 999999999),
            'hire_date' => $now->toDateString(),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return User::query()->create([
            'employee_id' => $employeeId,
            'login' => 'daily-gps-' . Str::lower(Str::random(10)),
            'password' => 'test-password',
            'status' => 'active',
        ]);
    }
}
