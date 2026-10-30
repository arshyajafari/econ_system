<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('scientific_visitor_inventory_transfers', function (Blueprint $table) {
            $table->date('delivered_at')->nullable()->after('returned_quantity');
        });

        // Preserve the original delivery day for every historical transfer.
        DB::statement('UPDATE scientific_visitor_inventory_transfers SET delivered_at = DATE(created_at) WHERE delivered_at IS NULL');
        Schema::table('scientific_visitor_inventory_transfers', function (Blueprint $table) {
            $table->index(['scientific_visitor_inventory_id', 'delivered_at'], 'svi_transfer_delivery_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('scientific_visitor_inventory_transfers', function (Blueprint $table) {
            $table->dropIndex('svi_transfer_delivery_date_idx');
            $table->dropColumn('delivered_at');
        });
    }
};
