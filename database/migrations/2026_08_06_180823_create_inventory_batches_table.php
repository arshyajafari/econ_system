<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('inventory_batches', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->foreignId('product_id')->constrained('products')->restrictOnDelete()->cascadeOnUpdate();
                $table->string('batch_number', 100)->nullable();
                $table->date('expire_date')->nullable();
                $table->unsignedInteger('quantity');
                $table->unsignedInteger('reserved_quantity')->default(0);
                $table->timestamp('received_at')->nullable();
                $table->description();
                $table->audit();
                $table->common();

                $table->index('product_id');
                $table->index('expire_date');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('inventory_batches');
        }
    };
