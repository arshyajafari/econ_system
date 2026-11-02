<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('employee_work_sessions', function (Blueprint $table) {
            $table->string('description', 255)->nullable()->after('work_date');
            $table->text('admin_correction_reason')->nullable()->after('end_operation_id');
            $table->foreignId('admin_corrected_by')->nullable()->after('admin_correction_reason')
                ->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('admin_corrected_at')->nullable()->after('admin_corrected_by');
        });
    }

    public function down(): void {
        Schema::table('employee_work_sessions', function (Blueprint $table) {
            $table->dropForeign(['admin_corrected_by']);
            $table->dropColumn([
                'description',
                'admin_correction_reason',
                'admin_corrected_by',
                'admin_corrected_at',
            ]);
        });
    }
};
