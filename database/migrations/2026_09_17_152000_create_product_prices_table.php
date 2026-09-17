<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()->cascadeOnUpdate();
            $table->decimal('sale_price', 15, 2);
            $table->dateTime('effective_from');
            $table->dateTime('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->description();
            $table->audit();
            $table->common();

            $table->index(['product_id', 'is_active']);
            $table->index(['product_id', 'effective_from']);
            $table->index(['product_id', 'effective_to']);
        });

        if (Schema::hasColumn('products', 'sale_price')) {
            DB::table('products')
                ->whereNotNull('sale_price')
                ->orderBy('id')
                ->each(function ($product): void {
                    DB::table('product_prices')->insert([
                        'public_id' => (string) \Illuminate\Support\Str::uuid(),
                        'product_id' => $product->id,
                        'sale_price' => $product->sale_price,
                        'effective_from' => $product->updated_at ?? $product->created_at ?? now(),
                        'effective_to' => null,
                        'is_active' => true,
                        'description' => 'Migrated from products.sale_price',
                        'created_at' => $product->created_at ?? now(),
                        'updated_at' => $product->updated_at ?? now(),
                    ]);
                });

            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('sale_price');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('products', 'sale_price')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('sale_price', 15, 2)->nullable()->after('title');
            });
        }

        DB::table('product_prices')
            ->where('is_active', true)
            ->orderBy('product_id')
            ->each(function ($price): void {
                DB::table('products')
                    ->where('id', $price->product_id)
                    ->update(['sale_price' => $price->sale_price]);
            });

        Schema::dropIfExists('product_prices');
    }
};
