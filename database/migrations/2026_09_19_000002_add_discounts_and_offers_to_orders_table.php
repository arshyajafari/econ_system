<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('discount_type', 30)->default('none')->after('description');
            $table->decimal('discount_value', 15, 2)->default(0)->after('discount_type');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_value');
            $table->string('offer_title', 255)->nullable()->after('discount_amount');
            $table->text('offer_description')->nullable()->after('offer_title');
            $table->index('discount_type');
        });
    }

    public function down(): void {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['discount_type']);
            $table->dropColumn(['discount_type','discount_value','discount_amount','offer_title','offer_description']);
        });
    }
};