<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('customer_credit_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('source_transaction_id')->constrained('customer_transactions')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('amount', 15, 2);
            $table->dateTime('allocated_at');
            $table->description();
            $table->audit();
            $table->common();

            $table->index(['customer_id', 'allocated_at']);
            // MySQL limits identifier names to 64 characters.
            $table->index(
                ['source_transaction_id', 'invoice_id'],
                'cca_source_invoice_idx'
            );
            $table->index('invoice_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('customer_credit_allocations');
    }
};
