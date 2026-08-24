<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('customer_assignments', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnUpdate()->restrictOnDelete();
                $table->boolean('is_primary')->default(false);
                $table->date('started_at');
                $table->date('ended_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->description();
                $table->audit();
                $table->common();

                $table->index([
                    'customer_id',
                    'employee_id'
                ]);
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('customer_assignments');
        }
    };
