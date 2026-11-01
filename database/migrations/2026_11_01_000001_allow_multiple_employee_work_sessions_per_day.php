<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // MySQL may require the existing composite unique index to support
        // the employee_id foreign key. Add a replacement index first.
        Schema::table('employee_work_sessions', function (Blueprint $table) {
            $table->index(
                ['employee_id', 'work_date', 'started_at'],
                'employee_work_sessions_employee_date_started_idx'
            );
        });

        // Drop the unique index only after a compatible employee_id-leading
        // index exists, so the foreign key remains valid.
        Schema::table('employee_work_sessions', function (Blueprint $table) {
            $table->dropUnique('employee_work_sessions_employee_id_work_date_unique');
        });
    }

    public function down(): void {
        // Keep multiple same-day missions intact; restoring the unique constraint
        // could fail once legitimate additional sessions have been recorded.
        Schema::table('employee_work_sessions', function (Blueprint $table) {
            $table->dropIndex('employee_work_sessions_employee_date_started_idx');
        });
    }
};
