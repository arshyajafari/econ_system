<?php

namespace Tests\Feature;

use App\Actions\OrderReturn\UpdateOrderReturnAction;
use App\Exceptions\BusinessRuleException;
use App\Models\OrderReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class UpdateOrderReturnValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_order_items_cannot_bypass_returnable_quantity_checks(): void
    {
        $context = $this->createReturnContext();

        try {
            app(UpdateOrderReturnAction::class)->execute($context['return'], [
                'items' => [
                    ['order_item_id' => $context['order_item_public_id'], 'quantity' => 6, 'free_quantity' => 0],
                    ['order_item_id' => $context['order_item_public_id'], 'quantity' => 6, 'free_quantity' => 0],
                ],
            ]);

            $this->fail('Duplicate order-item rows must not bypass aggregate returnable quantity limits.');
        } catch (BusinessRuleException $exception) {
            $this->assertStringContainsString('فقط یک بار', $exception->getMessage());
        }

        $this->assertDatabaseCount('order_return_items', 0);
        $this->assertDatabaseHas('order_returns', [
            'id' => $context['return']->id,
            'status' => 'draft',
        ]);
    }

    private function createReturnContext(): array
    {
        $now = now();

        $customerId = DB::table('customers')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'CUS-' . Str::upper(Str::random(8)),
            'customer_name' => 'Return update test customer',
            'type' => 'pharmacy',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $employeeId = DB::table('employees')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'EMP-' . Str::upper(Str::random(8)),
            'first_name' => 'Return',
            'last_name' => 'Tester',
            'national_code' => Str::upper(Str::random(10)),
            'phone_number' => '09' . random_int(100000000, 999999999),
            'hire_date' => $now->toDateString(),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $brandId = DB::table('brands')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'BR-' . Str::upper(Str::random(8)),
            'title' => 'Return update test brand',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $categoryId = DB::table('product_categories')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'CAT-' . Str::upper(Str::random(8)),
            'title' => 'Return update test category',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $productId = DB::table('products')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'PRO-' . Str::upper(Str::random(8)),
            'brand_id' => $brandId,
            'product_category_id' => $categoryId,
            'title' => 'Return update test product',
            'status' => 'active',
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'ORD-' . Str::upper(Str::random(8)),
            'customer_id' => $customerId,
            'sales_employee_id' => $employeeId,
            'status' => 'completed',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderItemPublicId = (string) Str::ulid();
        DB::table('order_items')->insert([
            'public_id' => $orderItemPublicId,
            'order_id' => $orderId,
            'product_id' => $productId,
            'quantity' => 10,
            'unit_price' => 100,
            'total_price' => 1000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $returnId = DB::table('order_returns')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'RET-' . Str::upper(Str::random(8)),
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'employee_id' => $employeeId,
            'status' => 'draft',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'return' => OrderReturn::query()->findOrFail($returnId),
            'order_item_public_id' => $orderItemPublicId,
        ];
    }
}
