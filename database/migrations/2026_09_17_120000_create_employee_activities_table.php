<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employee_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('activity_type', 50);
            $table->timestamps();
            $table->unique(['employee_id', 'activity_type']);
            $table->index('activity_type');
        });

        DB::table('employees')->select(['id', 'activity_type'])->whereNotNull('activity_type')->orderBy('id')->each(function ($employee): void {
            DB::table('employee_activities')->insert([
                'employee_id' => $employee->id,
                'activity_type' => $employee->activity_type,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void {
        Schema::dropIfExists('employee_activities');
    }
};
