<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmployeeWorkSessionTest extends TestCase {
    use RefreshDatabase;

    public function test_employee_can_start_and_end_only_one_daily_work_session(): void {
        $user = $this->createEmployeeUser();

        $start = [
            'latitude' => 36.5658,
            'longitude' => 53.0586,
            'accuracy' => 12.5,
            'client_operation_id' => '6d3d3f3e-77d9-4f4b-a0d5-9b2a2a3f0001',
        ];

        $first = $this->actingAs($user)->postJson('/api/v1/employees/locations/work-sessions/start', $start);
        $first->assertCreated()->assertJsonPath('data.status', 'in_progress');

        $replayed = $this->postJson('/api/v1/employees/locations/work-sessions/start', $start);
        $replayed->assertCreated()->assertJsonPath('data.id', $first->json('data.id'));
        $this->assertDatabaseCount('employee_work_sessions', 1);

        $duplicateStart = $this->postJson('/api/v1/employees/locations/work-sessions/start', [
            ...$start,
            'client_operation_id' => '6d3d3f3e-77d9-4f4b-a0d5-9b2a2a3f0002',
        ]);
        $duplicateStart->assertUnprocessable();
        $this->assertDatabaseCount('employee_work_sessions', 1);

        $end = [
            'latitude' => 36.5700,
            'longitude' => 53.0600,
            'accuracy' => 18,
            'client_operation_id' => '6d3d3f3e-77d9-4f4b-a0d5-9b2a2a3f0003',
        ];
        $finished = $this->postJson('/api/v1/employees/locations/work-sessions/end', $end);
        $finished->assertOk()->assertJsonPath('data.status', 'completed');

        $replayedEnd = $this->postJson('/api/v1/employees/locations/work-sessions/end', $end);
        $replayedEnd->assertOk()->assertJsonPath('data.id', $first->json('data.id'));
        $this->assertDatabaseCount('employee_work_sessions', 1);

        $secondEnd = $this->postJson('/api/v1/employees/locations/work-sessions/end', [
            ...$end,
            'client_operation_id' => '6d3d3f3e-77d9-4f4b-a0d5-9b2a2a3f0004',
        ]);
        $secondEnd->assertUnprocessable();
        $this->assertDatabaseCount('employee_work_sessions', 1);
    }

    public function test_employee_can_record_multiple_sequential_missions_in_one_day(): void {
        $user = $this->createEmployeeUser();
        $this->actingAs($user);

        $first = $this->postJson('/api/v1/employees/locations/work-sessions/start', [
            'latitude' => 36.5658, 'longitude' => 53.0586, 'accuracy' => 10,
            'client_operation_id' => '7d3d3f3e-77d9-4f4b-a0d5-9b2a2a3f0101',
        ]);
        $first->assertCreated()->assertJsonPath('data.status', 'in_progress');

        $this->postJson('/api/v1/employees/locations/work-sessions/end', [
            'latitude' => 36.5700, 'longitude' => 53.0600, 'accuracy' => 10,
            'client_operation_id' => '7d3d3f3e-77d9-4f4b-a0d5-9b2a2a3f0102',
        ])->assertOk()->assertJsonPath('data.status', 'completed');

        $second = $this->postJson('/api/v1/employees/locations/work-sessions/start', [
            'latitude' => 36.5800, 'longitude' => 53.0700, 'accuracy' => 10,
            'client_operation_id' => '7d3d3f3e-77d9-4f4b-a0d5-9b2a2a3f0103',
        ]);
        $second->assertCreated()->assertJsonPath('data.status', 'in_progress');
        $this->assertDatabaseCount('employee_work_sessions', 2);

        $this->getJson('/api/v1/employees/locations/work-sessions/me')
            ->assertOk()
            ->assertJsonPath('data.id', $second->json('data.id'));
    }

    public function test_employee_cannot_end_a_work_session_before_starting(): void {
        $user = $this->createEmployeeUser();

        $this->actingAs($user)->postJson('/api/v1/employees/locations/work-sessions/end', [
            'latitude' => 36.5658,
            'longitude' => 53.0586,
            'accuracy' => 10,
            'client_operation_id' => '6d3d3f3e-77d9-4f4b-a0d5-9b2a2a3f0005',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('employee_work_sessions', 0);
    }

    private function createEmployeeUser(): User {
        $now = now();
        $employeeId = DB::table('employees')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'EMP-' . Str::upper(Str::random(8)),
            'first_name' => 'GPS',
            'last_name' => 'Tester',
            'national_code' => Str::upper(Str::random(10)),
            'phone_number' => '09' . random_int(100000000, 999999999),
            'hire_date' => $now->toDateString(),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return User::query()->create([
            'employee_id' => $employeeId,
            'login' => 'gps-test-' . Str::lower(Str::random(10)),
            'password' => 'test-password',
            'status' => 'active',
        ]);
    }
}
