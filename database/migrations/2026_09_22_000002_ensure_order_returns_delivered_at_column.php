<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasColumn('order_returns', 'delivered_at')) {
            Schema::table('order_returns', function (Blueprint $table) {
                $table->timestamp('delivered_at')->nullable()->after('completed_at');
            });
        }
    }

    public function down(): void {
        if (Schema::hasColumn('order_returns', 'delivered_at')) {
            Schema::table('order_returns', function (Blueprint $table) {
                $table->dropColumn('delivered_at');
            });
        }
    }
};
