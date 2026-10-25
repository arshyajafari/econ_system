<?php

namespace Tests\Feature;

use App\Actions\Order\CancelOrderAction;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderCancellationDeliveryGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_with_pending_delivery_must_have_delivery_cancelled_first(): void
    {
        $order = $this->createOrderWithDelivery('pending');

        try {
            app(CancelOrderAction::class)->execute($order);
            $this->fail('An order with a pending delivery must not be cancelled directly.');
        } catch (BusinessRuleException $exception) {
            $this->assertSame('ابتدا ارسال مربوط به این سفارش را لغو کنید.', $exception->getMessage());
            $this->assertSame('confirmed', $order->fresh()->status->value);
        }
    }

    public function test_order_cannot_be_cancelled_after_delivery_has_been_shipped_or_delivered(): void
    {
        foreach (['shipped', 'delivered'] as $deliveryStatus) {
            $order = $this->createOrderWithDelivery($deliveryStatus);

            try {
                app(CancelOrderAction::class)->execute($order);
                $this->fail("An order with a {$deliveryStatus} delivery must not be cancelled.");
            } catch (BusinessRuleException $exception) {
                $this->assertSame(
                    'سفارشی که ارسال آن انجام شده یا به مشتری تحویل شده است قابل لغو نیست.',
                    $exception->getMessage()
                );
                $this->assertSame('confirmed', $order->fresh()->status->value);
            }
        }
    }

    public function test_order_can_be_cancelled_after_its_delivery_is_cancelled(): void
    {
        $order = $this->createOrderWithDelivery('cancelled');

        $cancelled = app(CancelOrderAction::class)->execute($order);

        $this->assertSame('cancelled', $cancelled->status->value);
    }

    private function createOrderWithDelivery(string $deliveryStatus): Order
    {
        $now = now();

        $customerId = DB::table('customers')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'CUS-' . Str::upper(Str::random(8)),
            'customer_name' => 'Order cancellation test customer',
            'type' => 'pharmacy',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $employeeId = DB::table('employees')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'EMP-' . Str::upper(Str::random(8)),
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'national_code' => 'N-' . Str::upper(Str::random(8)),
            'phone_number' => '09' . random_int(100000000, 999999999),
            'hire_date' => today()->toDateString(),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'ORD-' . Str::upper(Str::random(8)),
            'customer_id' => $customerId,
            'sales_employee_id' => $employeeId,
            'status' => 'confirmed',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('deliveries')->insert([
            'public_id' => (string) Str::ulid(),
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'employee_id' => $employeeId,
            'status' => $deliveryStatus,
            'prepared_at' => in_array($deliveryStatus, ['shipped', 'delivered'], true) ? $now : null,
            'shipped_at' => in_array($deliveryStatus, ['shipped', 'delivered'], true) ? $now : null,
            'delivered_at' => $deliveryStatus === 'delivered' ? $now : null,
            'cancelled_at' => $deliveryStatus === 'cancelled' ? $now : null,
            'recipient_name' => 'Test recipient',
            'recipient_phone' => '09120000000',
            'address' => 'Test address',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return Order::query()->findOrFail($orderId);
    }
}
