<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employee_locations', function (Blueprint $table) {
            $table->index(['employee_id', 'captured_at'], 'employee_locations_employee_captured_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('employee_locations', function (Blueprint $table) {
            $table->dropIndex('employee_locations_employee_captured_at_index');
        });
    }
};
