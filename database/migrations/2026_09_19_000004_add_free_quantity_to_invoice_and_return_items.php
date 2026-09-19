<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->unsignedInteger('free_quantity')->default(0)->after('quantity');
        });

        Schema::table('order_return_items', function (Blueprint $table) {
            $table->unsignedInteger('free_quantity')->default(0)->after('quantity');
        });
    }

    public function down(): void {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('free_quantity');
        });

        Schema::table('order_return_items', function (Blueprint $table) {
            $table->dropColumn('free_quantity');
        });
    }
};