<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('discount_type', 30)->default('none')->after('description');
            $table->decimal('discount_value', 15, 2)->default(0)->after('discount_type');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_value');
            $table->string('offer_type', 30)->default('none')->after('discount_amount');
            $table->unsignedInteger('offer_buy_quantity')->default(0)->after('offer_type');
            $table->unsignedInteger('offer_free_quantity')->default(0)->after('offer_buy_quantity');
            $table->string('offer_title', 255)->nullable()->after('offer_free_quantity');
            $table->index('offer_type');
        });
    }

    public function down(): void {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['offer_type']);
            $table->dropColumn([
                'discount_type','discount_value','discount_amount',
                'offer_type','offer_buy_quantity','offer_free_quantity','offer_title',
            ]);
        });
    }
};