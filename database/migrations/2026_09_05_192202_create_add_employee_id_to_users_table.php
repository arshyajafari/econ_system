<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('employee_id')->unique()->after('public_id')->constrained('employees')
                    ->cascadeOnUpdate()->restrictOnDelete();
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['employee_id']);
                $table->dropUnique(['employee_id']);
                $table->dropColumn('employee_id');
            });
        }
    };
