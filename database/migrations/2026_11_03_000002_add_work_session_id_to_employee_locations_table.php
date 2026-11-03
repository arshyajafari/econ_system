<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employee_locations', function (Blueprint $table) {
            $table->foreignId('work_session_id')
                ->nullable()
                ->after('employee_id')
                ->constrained('employee_work_sessions')
                ->cascadeOnDelete();

            $table->index(
                ['work_session_id', 'captured_at'],
                'employee_locations_work_session_captured_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('employee_locations', function (Blueprint $table) {
            $table->dropIndex('employee_locations_work_session_captured_idx');
            $table->dropForeign(['work_session_id']);
            $table->dropColumn('work_session_id');
        });
    }
};
