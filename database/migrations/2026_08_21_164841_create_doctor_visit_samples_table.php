<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('doctor_visit_samples', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->foreignId('doctor_visit_id')->constrained('doctor_visits')->cascadeOnDelete()
                    ->cascadeOnUpdate();
                $table->foreignId('product_id')->constrained('products')->restrictOnDelete()->cascadeOnUpdate();
                $table->unsignedInteger('quantity');
                $table->description();
                $table->common();

                $table->unique([
                    'doctor_visit_id',
                    'product_id',
                ]);
                $table->index('product_id');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('doctor_visit_samples');
        }
    };
