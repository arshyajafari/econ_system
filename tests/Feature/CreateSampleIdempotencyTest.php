<?php

namespace Tests\Feature;

use App\Actions\Sample\CreateSampleAction;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateSampleIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_replaying_the_same_operation_does_not_increase_sample_or_inventory_usage_twice(): void
    {
        $context = $this->createScientificVisitorContext();
        $action = app(CreateSampleAction::class);
        $firstRequest = [
            'visit_id' => $context['visit']->public_id,
            'product_id' => $context['product_public_id'],
            'quantity' => 2,
            'description' => 'First operation',
            'client_operation_id' => 'sample-operation-one',
        ];

        $first = $action->execute($firstRequest, $context['user']);
        $replayedFirst = $action->execute($firstRequest, $context['user']);

        $this->assertSame($first->id, $replayedFirst->id);
        $this->assertSame(2, $replayedFirst->quantity);
        $this->assertDatabaseHas('scientific_visitor_inventories', [
            'id' => $context['inventory_id'],
            'received_quantity' => 10,
            'used_quantity' => 2,
        ]);
        $this->assertDatabaseCount('sample_operations', 1);

        $secondRequest = [
            ...$firstRequest,
            'quantity' => 3,
            'description' => 'Second operation',
            'client_operation_id' => 'sample-operation-two',
        ];

        $second = $action->execute($secondRequest, $context['user']);
        $replayedSecond = $action->execute($secondRequest, $context['user']);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(5, $second->quantity);
        $this->assertSame(5, $replayedSecond->quantity);
        $this->assertDatabaseHas('scientific_visitor_inventories', [
            'id' => $context['inventory_id'],
            'received_quantity' => 10,
            'used_quantity' => 5,
        ]);
        $this->assertDatabaseCount('sample_operations', 2);
    }

    public function test_reusing_an_operation_id_with_different_payload_is_rejected_without_side_effects(): void
    {
        $context = $this->createScientificVisitorContext();
        $action = app(CreateSampleAction::class);
        $request = [
            'visit_id' => $context['visit']->public_id,
            'product_id' => $context['product_public_id'],
            'quantity' => 2,
            'description' => 'Original description',
            'client_operation_id' => 'sample-operation-conflict',
        ];

        $sample = $action->execute($request, $context['user']);

        try {
            $action->execute([
                ...$request,
                'quantity' => 4,
            ], $context['user']);
            $this->fail('Reusing an operation ID with a different payload must be rejected.');
        } catch (BusinessRuleException $exception) {
            $this->assertStringContainsString('شناسه عملیات قبلاً', $exception->getMessage());
        }

        $this->assertSame(2, $sample->fresh()->quantity);
        $this->assertDatabaseHas('scientific_visitor_inventories', [
            'id' => $context['inventory_id'],
            'used_quantity' => 2,
        ]);
        $this->assertDatabaseCount('sample_operations', 1);
    }

    public function test_an_operation_id_cannot_be_reused_for_another_visit(): void
    {
        $context = $this->createScientificVisitorContext();
        $action = app(CreateSampleAction::class);
        $request = [
            'visit_id' => $context['visit']->public_id,
            'product_id' => $context['product_public_id'],
            'quantity' => 1,
            'client_operation_id' => 'sample-operation-cross-visit',
        ];

        $action->execute($request, $context['user']);
        $otherVisitId = $this->createVisit(
            $context['employee_id'],
            $context['doctor_id']
        );

        try {
            $action->execute([
                ...$request,
                'visit_id' => $otherVisitId,
            ], $context['user']);
            $this->fail('An operation ID must not be reusable for another visit.');
        } catch (BusinessRuleException $exception) {
            $this->assertStringContainsString('شناسه عملیات قبلاً', $exception->getMessage());
        }

        $this->assertDatabaseHas('scientific_visitor_inventories', [
            'id' => $context['inventory_id'],
            'used_quantity' => 1,
        ]);
        $this->assertDatabaseCount('sample_operations', 1);
    }

    private function createScientificVisitorContext(): array
    {
        $now = now();

        $brandId = DB::table('brands')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'BR-' . Str::upper(Str::random(8)),
            'title' => 'Sample idempotency brand',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $categoryId = DB::table('product_categories')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'CAT-' . Str::upper(Str::random(8)),
            'title' => 'Sample idempotency category',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $productPublicId = (string) Str::ulid();
        $productId = DB::table('products')->insertGetId([
            'public_id' => $productPublicId,
            'code' => 'PRO-' . Str::upper(Str::random(8)),
            'brand_id' => $brandId,
            'product_category_id' => $categoryId,
            'title' => 'Sample idempotency product',
            'status' => 'active',
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $employeeId = DB::table('employees')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'EMP-' . Str::upper(Str::random(8)),
            'first_name' => 'Sample',
            'last_name' => 'Visitor',
            'national_code' => Str::upper(Str::random(10)),
            'phone_number' => '09' . random_int(100000000, 999999999),
            'hire_date' => $now->toDateString(),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $user = User::query()->create([
            'employee_id' => $employeeId,
            'login' => 'sample-visitor-' . Str::lower(Str::random(10)),
            'password' => 'test-password',
            'status' => 'active',
        ]);
        Role::findOrCreate('scientific visitor', 'web');
        $user->assignRole('scientific visitor');

        $doctorId = DB::table('doctors')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'DOC-' . Str::upper(Str::random(8)),
            'first_name' => 'Sample',
            'last_name' => 'Doctor',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $inventoryId = DB::table('scientific_visitor_inventories')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'employee_id' => $employeeId,
            'product_id' => $productId,
            'received_quantity' => 10,
            'used_quantity' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $visitPublicId = $this->createVisit($employeeId, $doctorId);

        return [
            'user' => $user,
            'employee_id' => $employeeId,
            'doctor_id' => $doctorId,
            'product_public_id' => $productPublicId,
            'inventory_id' => $inventoryId,
            'visit' => Visit::query()->where('public_id', $visitPublicId)->firstOrFail(),
        ];
    }

    private function createVisit(int $employeeId, int $doctorId): string
    {
        $publicId = (string) Str::ulid();
        DB::table('visits')->insert([
            'public_id' => $publicId,
            'doctor_id' => $doctorId,
            'employee_id' => $employeeId,
            'visit_date' => now(),
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $publicId;
    }
}
