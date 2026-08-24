<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('order_return_item_allocations', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->foreignId('order_return_item_id')->constrained('order_return_items')->cascadeOnDelete()
                    ->cascadeOnUpdate();
                $table->foreignId('inventory_batch_id')->constrained('inventory_batches')->restrictOnDelete()
                    ->cascadeOnUpdate();
                $table->unsignedInteger('quantity');
                $table->common();

                $table->unique([
                    'order_return_item_id',
                    'inventory_batch_id',
                ]);
                $table->index('inventory_batch_id');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('order_return_item_allocations');
        }
    };
