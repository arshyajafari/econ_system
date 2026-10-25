<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sample_operations', function (Blueprint $table) {
            $table->id();
            $table->string('client_operation_id', 64)->unique();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('sample_id')->nullable()->constrained('samples')->nullOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('quantity');
            $table->char('request_hash', 64);
            $table->timestamps();

            $table->index(['visit_id', 'product_id']);
            $table->index('sample_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sample_operations');
    }
};
