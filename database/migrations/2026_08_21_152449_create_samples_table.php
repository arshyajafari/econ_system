<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('samples', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('product_id')->constrained('products')->restrictOnDelete()->cascadeOnUpdate();
                $table->unsignedInteger('quantity');
                $table->description();
                $table->json('meta')->nullable();
                $table->audit();
                $table->common();

                $table->index('visit_id');
                $table->index('product_id');

                $table->unique([
                    'visit_id',
                    'product_id',
                ]);
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('samples');
        }
    };
