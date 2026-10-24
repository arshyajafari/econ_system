<?php

namespace Tests\Unit\Services;

use App\Services\CustomerLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerLedgerServiceTest extends TestCase
{
    private string $originalDefaultConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDefaultConnection = config('database.default');
        config([
            'database.connections.ledger_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);

        DB::purge('ledger_test');
        DB::setDefaultConnection('ledger_test');

        Schema::connection('ledger_test')->create('customers', function ($table): void {
            $table->id();
            $table->string('public_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::connection('ledger_test')->create('invoices', function ($table): void {
            $table->id();
            $table->string('public_id')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->string('code')->nullable();
            $table->string('status');
            $table->date('due_date')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::connection('ledger_test')->create('payments', function ($table): void {
            $table->id();
            $table->string('public_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::connection('ledger_test')->create('order_returns', function ($table): void {
            $table->id();
            $table->string('public_id')->nullable();
            $table->string('code')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::connection('ledger_test')->create('customer_credit_allocations', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('source_transaction_id');
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount', 15, 2);
            $table->timestamp('allocated_at')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::connection('ledger_test')->create('customer_transactions', function ($table): void {
            $table->id();
            $table->string('public_id')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('order_return_id')->nullable();
            $table->string('type');
            $table->decimal('amount', 15, 2);
            $table->timestamp('transaction_at');
            $table->text('description')->nullable();
            $table->text('meta')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        DB::table('customers')->insert([
            'id' => 7,
            'public_id' => 'CUS-000007',
            'customer_name' => 'Test Customer',
        ]);

        DB::table('invoices')->insert([
            'id' => 30,
            'public_id' => 'INV-PUBLIC-30',
            'customer_id' => 7,
            'code' => 'INV-000030',
            'status' => 'issued',
            'due_date' => '2026-12-31',
        ]);

        DB::table('payments')->insert([
            'id' => 40,
            'public_id' => 'PAY-PUBLIC-40',
            'reference_number' => 'PAY-000040',
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('ledger_test');
        DB::setDefaultConnection($this->originalDefaultConnection);

        parent::tearDown();
    }

    public function test_running_balances_and_totals_use_exact_minor_units(): void
    {
        DB::table('customer_transactions')->insert([
            [
                'id' => 1,
                'customer_id' => 7,
                'invoice_id' => 30,
                'payment_id' => null,
                'order_return_id' => null,
                'type' => 'debit',
                'amount' => '0.10',
                'transaction_at' => '2026-01-01 10:00:00',
            ],
            [
                'id' => 2,
                'customer_id' => 7,
                'invoice_id' => null,
                'payment_id' => 40,
                'order_return_id' => null,
                'type' => 'credit',
                'amount' => '0.20',
                'transaction_at' => '2026-01-02 10:00:00',
            ],
            [
                'id' => 3,
                'customer_id' => 7,
                'invoice_id' => null,
                'payment_id' => null,
                'order_return_id' => null,
                'type' => 'debit',
                'amount' => '0.30',
                'transaction_at' => '2026-01-03 10:00:00',
            ],
        ]);

        $ledger = (new CustomerLedgerService())->build(7);

        $this->assertSame(0.40, $ledger['total_debit']);
        $this->assertSame(0.20, $ledger['total_credit']);
        $this->assertSame(0.20, $ledger['closing_balance']);
        $this->assertSame(0.10, $ledger['transactions'][0]->balance);
        $this->assertSame(-0.10, $ledger['transactions'][1]->balance);
        $this->assertSame(0.20, $ledger['transactions'][2]->balance);
        $this->assertSame([
            'type' => 'invoice',
            'id' => 'INV-PUBLIC-30',
            'code' => 'INV-000030',
        ], $ledger['transactions'][0]->source);
    }

    public function test_filtered_ledger_opening_balance_is_calculated_in_minor_units(): void
    {
        DB::table('customer_transactions')->insert([
            [
                'id' => 1,
                'customer_id' => 7,
                'type' => 'debit',
                'amount' => '100.10',
                'transaction_at' => '2026-01-01 10:00:00',
            ],
            [
                'id' => 2,
                'customer_id' => 7,
                'type' => 'credit',
                'amount' => '0.10',
                'transaction_at' => '2026-02-01 10:00:00',
            ],
        ]);

        $ledger = (new CustomerLedgerService())->build(7, '2026-02-01', '2026-02-28');

        $this->assertSame(100.10, $ledger['opening_balance']);
        $this->assertSame(0.10, $ledger['total_credit']);
        $this->assertSame(100.00, $ledger['closing_balance']);
        $this->assertCount(1, $ledger['transactions']);
    }
}
