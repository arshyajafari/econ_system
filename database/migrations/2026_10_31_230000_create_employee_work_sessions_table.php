<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employee_work_sessions', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete()->cascadeOnUpdate();
            $table->date('work_date');
            $table->timestamp('started_at');
            $table->decimal('start_latitude', 10, 7);
            $table->decimal('start_longitude', 10, 7);
            $table->decimal('start_accuracy', 8, 2)->nullable();
            $table->string('start_operation_id', 64)->unique();
            $table->timestamp('ended_at')->nullable();
            $table->decimal('end_latitude', 10, 7)->nullable();
            $table->decimal('end_longitude', 10, 7)->nullable();
            $table->decimal('end_accuracy', 8, 2)->nullable();
            $table->string('end_operation_id', 64)->nullable()->unique();
            $table->common();
            $table->unique(['employee_id', 'work_date']);
            $table->index(['work_date', 'ended_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('employee_work_sessions');
    }
};
