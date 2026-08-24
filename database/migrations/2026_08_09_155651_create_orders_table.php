<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->string('code', 30)->unique();
                $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('sales_employee_id')->constrained('employees')->restrictOnDelete()->cascadeOnUpdate();
                $table->string('status', 30);
                $table->timestamp('ordered_at')->nullable();
                $table->description();
                $table->json('meta')->nullable();
                $table->audit();
                $table->common();

                $table->index('customer_id');
                $table->index('sales_employee_id');
                $table->index('status');
                $table->index('ordered_at');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('orders');
        }
    };
