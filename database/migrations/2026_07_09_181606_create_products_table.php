<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->string('code', 30)->unique();
                $table->foreignId('brand_id')->constrained('brands')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('product_category_id')->constrained('product_categories')->restrictOnDelete()
                    ->cascadeOnUpdate();
                $table->string('title', 300);
                $table->string('image', 500)->nullable();
                $table->string('barcode', 50)->unique()->nullable();
                $table->sort();
                $table->status();
                $table->description();
                $table->json('meta')->nullable();
                $table->audit();
                $table->common();

                $table->index([
                    'brand_id',
                    'product_category_id'
                ]);
                $table->index('title');
                $table->index('status');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('products');
        }
    };
