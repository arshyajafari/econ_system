<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('customer_transactions', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->restrictOnDelete()
                    ->cascadeOnUpdate();
                $table->foreignId('payment_id')->nullable()->constrained('payments')->restrictOnDelete()
                    ->cascadeOnUpdate();
                $table->foreignId('order_return_id')->nullable()->constrained('order_returns')->restrictOnDelete()
                    ->cascadeOnUpdate();
                $table->string('type', 20);
                $table->decimal('amount', 15, 2);
                $table->dateTime('transaction_at');
                $table->description();
                $table->json('meta')->nullable();
                $table->audit();
                $table->common();

                $table->index([
                    'customer_id',
                    'transaction_at'
                ]);
                $table->index('type');

                $table->unique('invoice_id');
                $table->unique('payment_id');
                $table->unique('order_return_id');
            });

        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('customer_transactions');
        }
    };
