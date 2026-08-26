<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('visits', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->string('client_operation_id', 64)->nullable()->unique();
                $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete()->cascadeOnUpdate();
                $table->dateTime('visit_date');
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->decimal('location_accuracy', 8, 2)->nullable();
                $table->timestamp('location_captured_at')->nullable();
                $table->string('purpose', 150)->nullable();
                $table->status();
                $table->description();
                $table->json('meta')->nullable();
                $table->audit();
                $table->common();

                $table->index('doctor_id');
                $table->index('employee_id');
                $table->index('status');
                $table->index('visit_date');

                $table->index([
                    'doctor_id',
                    'visit_date',
                ]);
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('visits');
        }
    };
