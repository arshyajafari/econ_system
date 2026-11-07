<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_activity_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('employee_name_snapshot', 160);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('visit_date');
            $table->time('visit_time')->nullable();
            $table->string('organization_name', 180);
            $table->string('contact_name', 160)->nullable();
            $table->string('contact_type', 20);
            $table->string('purpose', 200);
            $table->text('discussion');
            $table->text('employee_question')->nullable();
            $table->text('audience_feedback')->nullable();
            $table->text('outcome');
            $table->string('result', 20);
            $table->date('retention_until');
            $table->timestamps();
            $table->index(['employee_id', 'visit_date']);
            $table->index(['retention_until', 'visit_date']);
            $table->index(['result', 'contact_type']);
        });

        Schema::create('daily_activity_report_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('daily_activity_reports')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 500);
            $table->json('old_values');
            $table->json('new_values');
            $table->timestamp('created_at');
            $table->index(['report_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_activity_report_revisions');
        Schema::dropIfExists('daily_activity_reports');
    }
};
