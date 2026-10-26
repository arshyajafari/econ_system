<?php

namespace Tests\Feature;

use App\Actions\Delivery\CompleteDeliveryAction;
use App\Actions\Delivery\CreateDeliveryAction;
use App\Enums\DeliveryStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreateDeliveryContactValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register_delivery_without_knowing_receiver_or_address_yet(): void
    {
        [$user, $orderPublicId] = $this->createOrderContext();

        $delivery = app(CreateDeliveryAction::class)->execute([
            'order_id' => $orderPublicId,
            'recipient_name' => null,
            'recipient_phone' => null,
            'address' => null,
        ], $user);

        $this->assertNull($delivery->recipient_name);
        $this->assertSame('09121111111', $delivery->recipient_phone);
        $this->assertNull($delivery->address);
    }

    public function test_creating_delivery_uses_customer_defaults_when_contact_fields_are_blank(): void
    {
        [$user, $orderPublicId] = $this->createOrderContext();

        $order = DB::table('orders')->where('public_id', $orderPublicId)->first();
        DB::table('customer_addresses')->insert([
            'customer_id' => $order->customer_id,
            'province' => 'Mazandaran',
            'city' => 'Sari',
            'address' => 'Customer default delivery address',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $delivery = app(CreateDeliveryAction::class)->execute([
            'order_id' => $orderPublicId,
            'recipient_name' => '',
            'recipient_phone' => '',
            'address' => '',
        ], $user);

        $this->assertSame('09121111111', $delivery->recipient_phone);
        $this->assertSame('Customer default delivery address', $delivery->address);
    }

    public function test_handoff_uses_customer_default_address_when_address_is_not_entered(): void
    {
        [$user, $orderPublicId] = $this->createOrderContext();

        $delivery = app(CreateDeliveryAction::class)->execute([
            'order_id' => $orderPublicId,
            'recipient_name' => null,
            'recipient_phone' => null,
            'address' => null,
        ], $user);

        DB::table('customer_addresses')->insert([
            'customer_id' => $delivery->customer_id,
            'province' => 'Mazandaran',
            'city' => 'Sari',
            'address' => 'Customer default delivery address',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $delivery->status = DeliveryStatus::SHIPPED;
        $delivery->save();

        $completed = app(CompleteDeliveryAction::class)->execute($delivery, [
            'recipient_name' => 'Receiver Name',
            'recipient_phone' => '',
            'address' => '',
        ], $user);

        $this->assertSame('Customer default delivery address', $completed->address);
        $this->assertSame('09121111111', $completed->recipient_phone);
        $this->assertSame('Receiver Name', $completed->recipient_name);
    }

    public function test_handoff_prefers_explicit_address_over_customer_default_address(): void
    {
        [$user, $orderPublicId] = $this->createOrderContext();

        $delivery = app(CreateDeliveryAction::class)->execute([
            'order_id' => $orderPublicId,
            'recipient_name' => null,
            'recipient_phone' => null,
            'address' => null,
        ], $user);

        DB::table('customer_addresses')->insert([
            'customer_id' => $delivery->customer_id,
            'province' => 'Mazandaran',
            'city' => 'Sari',
            'address' => 'Customer default delivery address',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $delivery->status = DeliveryStatus::SHIPPED;
        $delivery->save();

        $completed = app(CompleteDeliveryAction::class)->execute($delivery, [
            'recipient_name' => 'Receiver Name',
            'recipient_phone' => '',
            'address' => 'Specific handoff address',
        ], $user);

        $this->assertSame('Specific handoff address', $completed->address);
    }

    public function test_delivery_can_be_registered_without_a_phone_when_customer_has_no_phone(): void
    {
        [$user, $orderPublicId] = $this->createOrderContext(customerPhone: null);

        $delivery = app(CreateDeliveryAction::class)->execute([
            'order_id' => $orderPublicId,
            'recipient_name' => null,
            'recipient_phone' => null,
            'address' => null,
        ], $user);

        $this->assertNull($delivery->recipient_phone);
    }

    private function createOrderContext(?string $customerPhone = '09121111111'): array
    {
        $now = now();

        $customerId = DB::table('customers')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'CUS-' . Str::upper(Str::random(8)),
            'customer_name' => 'Delivery contact test customer',
            'type' => 'pharmacy',
            'status' => 'active',
            'phone_number' => $customerPhone,
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

        $userId = DB::table('users')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'employee_id' => $employeeId,
            'login' => 'delivery-test-' . Str::lower(Str::random(8)),
            'password' => bcrypt('test-password'),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderPublicId = (string) Str::ulid();
        $orderId = DB::table('orders')->insertGetId([
            'public_id' => $orderPublicId,
            'code' => 'ORD-' . Str::upper(Str::random(8)),
            'customer_id' => $customerId,
            'sales_employee_id' => $employeeId,
            'status' => 'completed',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('invoices')->insert([
            'public_id' => (string) Str::ulid(),
            'code' => 'INV-' . Str::upper(Str::random(8)),
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'employee_id' => $employeeId,
            'status' => 'issued',
            'issued_at' => $now,
            'subtotal' => 100,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [User::query()->findOrFail($userId), $orderPublicId];
    }
}
