<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scientific_visitor_inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scientific_visitor_inventory_id')
                ->constrained('scientific_visitor_inventories')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('inventory_batch_id')
                ->constrained('inventory_batches')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('used_quantity')->default(0);
            $table->unsignedInteger('returned_quantity')->default(0);
            $table->timestamps();
            $table->index(['scientific_visitor_inventory_id', 'id'], 'svi_transfer_inventory_idx');
            $table->index('inventory_batch_id', 'svi_transfer_batch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scientific_visitor_inventory_transfers');
    }
};
