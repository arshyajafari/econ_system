<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scientific_visitor_inventories', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('received_quantity')->default(0);
            $table->unsignedInteger('used_quantity')->default(0);
            $table->timestamp('last_received_at')->nullable();
            $table->description();
            $table->audit();
            $table->common();

            $table->unique(['employee_id', 'product_id']);
            $table->index('employee_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scientific_visitor_inventories');
    }
};