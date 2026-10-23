<?php

namespace Tests\Unit\Services;

use App\Models\CustomerCreditAllocation;
use App\Models\CustomerTransaction;
use App\Models\Invoice;
use App\Services\CustomerCreditService as ServicesCustomerCreditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerCreditServiceTest extends TestCase
{
    private string $originalDefaultConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDefaultConnection = config('database.default');

        config([
            'database.connections.credit_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);

        DB::purge('credit_test');
        DB::setDefaultConnection('credit_test');

        Schema::connection('credit_test')->create('invoices', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('code');
            $table->decimal('total_amount', 15, 2);
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::connection('credit_test')->create('payments', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('status');
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('settlement_discount_amount', 15, 2)->default(0);
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::connection('credit_test')->create('order_returns', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('status');
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::connection('credit_test')->create('customer_transactions', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('order_return_id')->nullable();
            $table->string('type');
            $table->decimal('amount', 15, 2);
            $table->timestamp('transaction_at');
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::connection('credit_test')->create('customer_credit_allocations', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('source_transaction_id');
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount', 15, 2);
            $table->timestamp('allocated_at');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        DB::table('invoices')->insert([
            'id' => 10,
            'customer_id' => 7,
            'order_id' => 50,
            'code' => 'INV-000010',
            'total_amount' => 100,
        ]);

        DB::table('order_returns')->insert([
            'id' => 90,
            'order_id' => 50,
            // Completion happens after the credit is allocated in the real flow.
            'status' => 'confirmed',
        ]);

        DB::table('customer_transactions')->insert([
            'id' => 1,
            'customer_id' => 7,
            'order_return_id' => 90,
            'type' => 'credit',
            'amount' => 80,
            'transaction_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('credit_test');
        DB::setDefaultConnection($this->originalDefaultConnection);

        parent::tearDown();
    }

    public function test_repeated_allocation_cannot_spend_the_same_return_credit_twice(): void
    {
        $service = new ServicesCustomerCreditService();
        $source = CustomerTransaction::query()->findOrFail(1);
        $invoice = Invoice::query()->findOrFail(10);

        $firstAllocation = $service->allocateSourceToInvoice($source, $invoice, 60);
        // Reuse the same stale in-memory source and invoice objects, as can
        // happen when a caller loops over already-loaded models.
        $secondAllocation = $service->allocateSourceToInvoice($source, $invoice, 60);

        $this->assertSame(60.0, $firstAllocation);
        $this->assertSame(20.0, $secondAllocation);
        $this->assertSame(
            80.0,
            (float) CustomerCreditAllocation::query()
                ->where('source_transaction_id', 1)
                ->sum('amount'),
        );
    }

    public function test_allocation_does_not_exceed_the_invoice_remaining_balance(): void
    {
        DB::table('order_returns')->insert([
            'id' => 91,
            'order_id' => 50,
            'status' => 'confirmed',
        ]);

        DB::table('customer_transactions')->insert([
            'id' => 2,
            'customer_id' => 7,
            'order_return_id' => 91,
            'type' => 'credit',
            'amount' => 80,
            'transaction_at' => now(),
        ]);

        $service = new ServicesCustomerCreditService();
        $invoice = Invoice::query()->findOrFail(10);

        $first = $service->allocateSourceToInvoice(CustomerTransaction::query()->findOrFail(1), $invoice, 80);
        $second = $service->allocateSourceToInvoice(CustomerTransaction::query()->findOrFail(2), $invoice, 80);

        $this->assertSame(80.0, $first);
        $this->assertSame(20.0, $second);
        $this->assertSame(
            100.0,
            (float) CustomerCreditAllocation::query()
                ->where('invoice_id', 10)
                ->sum('amount'),
        );
    }
}
