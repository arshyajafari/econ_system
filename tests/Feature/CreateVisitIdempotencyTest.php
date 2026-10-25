<?php

namespace Tests\Feature;

use App\Actions\Visit\CreateVisitAction;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreateVisitIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_replaying_the_same_visit_operation_returns_the_original_visit(): void
    {
        $context = $this->createContext();
        $data = $this->visitData($context['doctor_public_id'], 'visit-operation-one');
        $action = app(CreateVisitAction::class);

        $first = $action->execute($data, $context['user']);
        $replayed = $action->execute($data, $context['user']);

        $this->assertSame($first->id, $replayed->id);
        $this->assertDatabaseCount('visits', 1);
    }

    public function test_reusing_a_visit_operation_id_with_different_payload_is_rejected(): void
    {
        $context = $this->createContext();
        $data = $this->visitData($context['doctor_public_id'], 'visit-operation-conflict');
        $action = app(CreateVisitAction::class);
        $action->execute($data, $context['user']);

        try {
            $action->execute([
                ...$data,
                'purpose' => 'Different purpose',
            ], $context['user']);
            $this->fail('An operation ID must not silently return a visit for a different payload.');
        } catch (BusinessRuleException $exception) {
            $this->assertStringContainsString('شناسه عملیات قبلاً', $exception->getMessage());
        }

        $this->assertDatabaseCount('visits', 1);
    }

    public function test_a_different_employee_cannot_reuse_an_existing_visit_operation_id(): void
    {
        $context = $this->createContext();
        $data = $this->visitData($context['doctor_public_id'], 'visit-operation-owner');
        $action = app(CreateVisitAction::class);
        $action->execute($data, $context['user']);

        $otherContext = $this->createContext();

        try {
            $action->execute($data, $otherContext['user']);
            $this->fail('An operation ID created by another employee must not be reused.');
        } catch (BusinessRuleException $exception) {
            $this->assertStringContainsString('کارمند دیگری', $exception->getMessage());
        }

        $this->assertDatabaseCount('visits', 1);
    }

    private function visitData(string $doctorPublicId, string $operationId): array
    {
        return [
            'doctor_id' => $doctorPublicId,
            'visit_date' => now()->format('Y-m-d H:i:s'),
            'purpose' => 'Product introduction',
            'description' => 'Test visit description',
            'client_operation_id' => $operationId,
        ];
    }

    private function createContext(): array
    {
        $now = now();
        $employeeId = DB::table('employees')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'EMP-' . Str::upper(Str::random(8)),
            'first_name' => 'Visit',
            'last_name' => 'Tester',
            'national_code' => Str::upper(Str::random(10)),
            'phone_number' => '09' . random_int(100000000, 999999999),
            'hire_date' => $now->toDateString(),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $user = User::query()->create([
            'employee_id' => $employeeId,
            'login' => 'visit-test-' . Str::lower(Str::random(10)),
            'password' => 'test-password',
            'status' => 'active',
        ]);

        $doctorPublicId = (string) Str::ulid();
        DB::table('doctors')->insert([
            'public_id' => $doctorPublicId,
            'code' => 'DOC-' . Str::upper(Str::random(8)),
            'first_name' => 'Visit',
            'last_name' => 'Doctor',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return ['user' => $user, 'doctor_public_id' => $doctorPublicId];
    }
}
