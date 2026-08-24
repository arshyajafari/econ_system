<?php

    use App\Enums\PaymentStatus;
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete()->cascadeOnUpdate();
                $table->string('status', 20)->default(PaymentStatus::PENDING->value);
                $table->string('method', 30);
                $table->decimal('amount', 15, 2);
                $table->string('reference_number', 100)->nullable();
                $table->date('payment_date');
                $table->description();
                $table->json('meta')->nullable();
                $table->audit();
                $table->common();

                $table->index('invoice_id');
                $table->index('customer_id');
                $table->index('employee_id');
                $table->index('status');
                $table->index('method');
                $table->index('payment_date');
                $table->index('reference_number');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('payments');
        }
    };
