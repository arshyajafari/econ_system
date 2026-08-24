<?php

    use App\Enums\InvoiceStatus;
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->string('code', 30)->unique();
                $table->foreignId('order_id')->unique()->constrained('orders')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete()->cascadeOnUpdate();
                $table->string('status', 30)->default(InvoiceStatus::DRAFT->value);
                $table->timestamp('issued_at')->nullable();
                $table->date('due_date')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->description();
                $table->json('meta')->nullable();
                $table->audit();
                $table->common();

                $table->index('customer_id');
                $table->index('employee_id');
                $table->index('status');
                $table->index('issued_at');
                $table->index('due_date');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('invoices');
        }
    };
