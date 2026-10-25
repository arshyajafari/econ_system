<?php

namespace Tests\\Feature;

use App\\Actions\\Delivery\\UpdateDeliveryAction;
use App\\Models\\Delivery;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Str;
use Tests\\TestCase;

class UpdateDeliveryPartialFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_omitted_delivery_fields_are_preserved_when_updating_recipient_name_only(): void
    {
        $delivery = $this->createDelivery();

        $updated = app(UpdateDeliveryAction::class)->execute($delivery, [
            'recipient_name' => 'Updated recipient',
        ]);

        $this->assertSame('Updated recipient', $updated->recipient_name);
        $this->assertSame('09120000000', $updated->recipient_phone);
        $this->assertSame('Existing delivery address', $updated->address);
        $this->assertSame('Keep this note', $updated->description);
        $this->assertSame('Mazandaran', $updated->meta['province']);
        $this->assertSame('Sari', $updated->meta['city']);
    }

    public function test_explicit_null_description_can_still_clear_the_description(): void
    {
        $delivery = $this->createDelivery();

        $updated = app(UpdateDeliveryAction::class)->execute($delivery, [
            'recipient_name' => $delivery->recipient_name,
            'description' => null,
        ]);

        $this->assertNull($updated->description);
    }

    private function createDelivery(): Delivery
    {
        $now = now();

        $customerId = DB::table('customers')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'CUS-' . Str::upper(Str::random(8)),
            'customer_name' => 'Delivery update test customer',
            'type' => 'pharmacy',
            'status' => 'active',
            'phone_number' => '09121111111',
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
            'status' => 'completed',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $deliveryId = DB::table('deliveries')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'employee_id' => $employeeId,
            'status' => 'pending',
            'recipient_name' => 'Original recipient',
            'recipient_phone' => '09120000000',
            'address' => 'Existing delivery address',
            'description' => 'Keep this note',
            'meta' => json_encode(['province' => 'Mazandaran', 'city' => 'Sari']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return Delivery::query()->findOrFail($deliveryId);
    }
}
