<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('title', 150);
            $table->decimal('amount', 18, 2);
            $table->string('category', 50);
            $table->date('expense_date');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['expense_date', 'category']);
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
