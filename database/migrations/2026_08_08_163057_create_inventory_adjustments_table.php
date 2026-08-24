<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('inventory_adjustments', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->foreignId('inventory_batch_id')->constrained('inventory_batches')->restrictOnDelete()
                    ->cascadeOnUpdate();
                $table->string('type', 20);
                $table->unsignedInteger('quantity');
                $table->string('reason', 300)->nullable();
                $table->description();
                $table->audit();
                $table->common();

                $table->index('type');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('inventory_adjustments');
        }
    };
