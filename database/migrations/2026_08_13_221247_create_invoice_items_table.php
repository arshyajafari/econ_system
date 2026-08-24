<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('invoice_items', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('order_item_id')->nullable()->constrained('order_items')->restrictOnDelete()
                    ->cascadeOnUpdate();
                $table->foreignId('product_id')->constrained('products')->restrictOnDelete()->cascadeOnUpdate();
                $table->unsignedInteger('quantity');
                $table->decimal('unit_price', 15, 2);
                $table->decimal('total_price', 15, 2);
                $table->description();
                $table->audit();
                $table->common();

                $table->index('invoice_id');
                $table->index('order_item_id');
                $table->index('product_id');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('invoice_items');
        }
    };
