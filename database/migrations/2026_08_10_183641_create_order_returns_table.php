<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('order_returns', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->string('code', 30)->unique();
                $table->foreignId('order_id')->constrained('orders')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete()->cascadeOnUpdate();
                $table->string('status', 30);
                $table->timestamp('completed_at')->nullable();
                $table->description();
                $table->json('meta')->nullable();
                $table->audit();
                $table->common();

                $table->index('order_id');
                $table->index('customer_id');
                $table->index('employee_id');
                $table->index('status');
                $table->index('completed_at');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('order_returns');
        }
    };
