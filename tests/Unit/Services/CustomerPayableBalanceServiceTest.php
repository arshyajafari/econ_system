<?php

namespace Tests\Unit\Services;

use App\Enums\DeliveryStatus;
use App\Enums\InvoiceStatus;
use App\Services\CustomerCreditService;
use App\Services\CustomerPayableBalanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class CustomerPayableBalanceServiceTest extends TestCase
{
    private string $originalDefaultConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDefaultConnection = config('database.default');
        config([
            'database.connections.payable_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);

        DB::purge('payable_test');
        DB::setDefaultConnection('payable_test');

        Schema::connection('payable_test')->create('orders', function ($table): void {
            $table->id();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::connection('payable_test')->create('deliveries', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('status');
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::connection('payable_test')->create('invoices', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('order_id');
            $table->string('code');
            $table->string('status');
            $table->timestamp('issued_at')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::connection('payable_test')->create('payments', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->string('status');
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('settlement_discount_amount', 15, 2)->default(0);
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::connection('payable_test')->create('order_returns', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('status');
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::connection('payable_test')->create('customer_transactions', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('order_return_id')->nullable();
            $table->string('type');
            $table->decimal('amount', 15, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamp('transaction_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::connection('payable_test')->create('customer_credit_allocations', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('source_transaction_id');
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount', 15, 2);
            $table->timestamp('allocated_at')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        DB::table('orders')->insert([['id' => 10], ['id' => 11]]);
        DB::table('deliveries')->insert([
            ['id' => 20, 'order_id' => 10, 'status' => DeliveryStatus::DELIVERED->value],
            ['id' => 21, 'order_id' => 11, 'status' => DeliveryStatus::SHIPPED->value],
        ]);
        DB::table('invoices')->insert([
            [
                'id' => 30,
                'customer_id' => 7,
                'order_id' => 10,
                'code' => 'INV-000030',
                'status' => InvoiceStatus::ISSUED->value,
                'issued_at' => now(),
                'total_amount' => 100,
            ],
            [
                'id' => 31,
                'customer_id' => 7,
                'order_id' => 11,
                'code' => 'INV-000031',
                'status' => InvoiceStatus::ISSUED->value,
                'issued_at' => now()->addMinute(),
                'total_amount' => 250,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('payable_test');
        DB::setDefaultConnection($this->originalDefaultConnection);

        parent::tearDown();
    }

    public function test_unallocated_return_credit_from_a_settled_invoice_reduces_other_invoice_payable_balance(): void
    {
        DB::table('invoices')->where('id', 30)->update(['total_amount' => 5000000]);
        DB::table('invoices')->where('id', 31)->update(['total_amount' => 5000000]);
        DB::table('deliveries')->where('id', 21)->update(['status' => DeliveryStatus::DELIVERED->value]);

        DB::table('payments')->insert([
            'id' => 100,
            'invoice_id' => 30,
            'customer_id' => 7,
            'status' => 'confirmed',
            'amount' => 5000000,
            'settlement_discount_amount' => 0,
        ]);

        DB::table('order_returns')->insert([
            'id' => 200,
            'order_id' => 10,
            'status' => 'completed',
        ]);

        DB::table('customer_transactions')->insert([
            'id' => 300,
            'customer_id' => 7,
            'order_return_id' => 200,
            'type' => 'credit',
            'amount' => 2000000,
            'transaction_at' => now(),
        ]);

        $service = new CustomerPayableBalanceService(new CustomerCreditService());

        $this->assertSame(3000000.0, $service->calculate(7));
    }

    public function test_fully_paid_invoice_return_credit_reduces_next_invoice_payable_to_eight_hundred(): void
    {
        DB::table('invoices')->where('id', 30)->update(['total_amount' => 1000]);
        DB::table('invoices')->where('id', 31)->update(['total_amount' => 1000]);
        DB::table('deliveries')->where('id', 21)->update([
            'status' => DeliveryStatus::DELIVERED->value,
        ]);

        DB::table('payments')->insert([
            'id' => 102,
            'invoice_id' => 30,
            'customer_id' => 7,
            'status' => 'confirmed',
            'amount' => 1000,
            'settlement_discount_amount' => 0,
        ]);

        DB::table('order_returns')->insert([
            'id' => 202,
            'order_id' => 10,
            'status' => 'completed',
        ]);

        DB::table('customer_transactions')->insert([
            'id' => 302,
            'customer_id' => 7,
            'order_return_id' => 202,
            'type' => 'credit',
            'amount' => 200,
            'transaction_at' => now(),
        ]);

        $service = new CustomerPayableBalanceService(new CustomerCreditService());

        // Invoice 30 is fully paid, so the 200 return credit remains reusable.
        // It offsets invoice 31 exactly once: 1,000 - 200 = 800 payable.
        $this->assertSame(800.0, $service->calculate(7));
    }

    public function test_return_credit_reduces_origin_invoice_without_being_subtracted_twice_from_customer_balance(): void
    {
        DB::table('deliveries')->where('id', 21)->update([
            'status' => DeliveryStatus::DELIVERED->value,
        ]);

        DB::table('payments')->insert([
            'id' => 101,
            'invoice_id' => 30,
            'customer_id' => 7,
            'status' => 'confirmed',
            'amount' => 50,
            'settlement_discount_amount' => 0,
        ]);

        DB::table('order_returns')->insert([
            'id' => 201,
            'order_id' => 10,
            'status' => 'completed',
        ]);

        DB::table('customer_transactions')->insert([
            'id' => 301,
            'customer_id' => 7,
            'order_return_id' => 201,
            'type' => 'credit',
            'amount' => 20,
            'transaction_at' => now(),
        ]);

        $service = new CustomerPayableBalanceService(new CustomerCreditService());

        // Invoice 30 retains 30 after payment and return credit; invoice 31
        // contributes 250. The same 20 return credit must not be deducted
        // a second time as reusable customer credit.
        $this->assertSame(280.0, $service->calculate(7));
    }

    public function test_only_issued_invoices_with_confirmed_delivery_contribute_to_payable_balance(): void
    {
        $credits = Mockery::mock(CustomerCreditService::class);
        $credits->shouldReceive('availableAmount')->twice()->with(7)->andReturn(0.0);

        $service = new CustomerPayableBalanceService($credits);

        $this->assertSame(100.0, $service->calculate(7));

        DB::table('deliveries')->where('id', 20)->update([
            'status' => DeliveryStatus::SHIPPED->value,
        ]);

        $this->assertSame(0.0, $service->calculate(7));
    }

    public function test_unlinked_customer_ledger_debt_and_credit_are_included_without_double_counting_source_transactions(): void
    {
        DB::table('customer_transactions')->insert([
            [
                'id' => 410,
                'customer_id' => 7,
                'invoice_id' => null,
                'payment_id' => null,
                'order_return_id' => null,
                'type' => 'debit',
                'amount' => 500,
                'transaction_at' => now(),
            ],
            [
                'id' => 411,
                'customer_id' => 7,
                'invoice_id' => null,
                'payment_id' => null,
                'order_return_id' => null,
                'type' => 'credit',
                'amount' => 100,
                'transaction_at' => now(),
            ],
            [
                'id' => 412,
                'customer_id' => 7,
                'invoice_id' => 30,
                'payment_id' => null,
                'order_return_id' => null,
                'type' => 'debit',
                'amount' => 900,
                'transaction_at' => now(),
            ],
            [
                'id' => 413,
                'customer_id' => 7,
                'invoice_id' => null,
                'payment_id' => 999,
                'order_return_id' => null,
                'type' => 'credit',
                'amount' => 800,
                'transaction_at' => now(),
            ],
        ]);

        $credits = Mockery::mock(CustomerCreditService::class);
        $credits->shouldReceive('availableAmount')->once()->with(7)->andReturn(0.0);

        $service = new CustomerPayableBalanceService($credits);

        $this->assertSame(400.0, $service->customerLevelBalance(7));
        // 100 invoice debt + (500 debit - 100 credit) standalone ledger balance.
        // Linked transactions are intentionally ignored because their source records
        // already participate in the balance calculation.
        $this->assertSame(500.0, $service->calculate(7));
    }

    public function test_imported_opening_balance_is_included_in_customer_payable_balance(): void
    {
        DB::table('customer_transactions')->insert([
            'id' => 400,
            'customer_id' => 7,
            'type' => 'debit',
            'amount' => 1250,
            'transaction_at' => now(),
            'meta' => json_encode([
                'source' => \App\Services\CustomerOpeningBalanceImportService::SOURCE,
                'original_balance' => '1250.00',
            ]),
        ]);

        $credits = Mockery::mock(CustomerCreditService::class);
        $credits->shouldReceive('availableAmount')->once()->with(7)->andReturn(0.0);

        $service = new CustomerPayableBalanceService($credits);

        $this->assertSame(1350.0, $service->calculate(7));
    }

}
