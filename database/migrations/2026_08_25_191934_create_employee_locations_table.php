<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('employee_locations', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->string('client_operation_id', 64)->nullable()->unique();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete()->cascadeOnUpdate();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->decimal('accuracy', 8, 2)->nullable();
                $table->string('source', 20)->default('gps');
                $table->timestamp('captured_at');
                $table->json('meta')->nullable();
                $table->common();

                $table->index([
                    'employee_id',
                    'captured_at',
                ]);
                $table->index('captured_at');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('employee_locations');
        }
    };
