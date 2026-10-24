<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('zero_stock_since')->nullable()->index();
        });

        // Give products already out of stock a full 30-day grace period from
        // deployment instead of archiving them immediately.
        DB::table('products')
            ->whereNull('deleted_at')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('inventory_batches')
                    ->whereColumn('inventory_batches.product_id', 'products.id')
                    ->whereNull('inventory_batches.deleted_at')
                    ->where('inventory_batches.quantity', '>', 0);
            })
            ->update(['zero_stock_since' => now()]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['zero_stock_since']);
            $table->dropColumn('zero_stock_since');
        });
    }
};
