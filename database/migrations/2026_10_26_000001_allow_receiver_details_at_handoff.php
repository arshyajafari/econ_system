<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table): void {
            $table->string('recipient_name', 150)->nullable()->change();
            $table->string('recipient_phone', 30)->nullable()->change();
            $table->text('address')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Existing records created before this migration may have no receiver
        // details. Do not force invalid placeholder values during rollback.
        // Restore non-null constraints only when every row has valid values.
        if (
            Schema::hasColumn('deliveries', 'recipient_name')
            && Schema::hasColumn('deliveries', 'recipient_phone')
            && Schema::hasColumn('deliveries', 'address')
            && !\Illuminate\Support\Facades\DB::table('deliveries')
                ->whereNull('recipient_name')
                ->orWhereNull('recipient_phone')
                ->orWhereNull('address')
                ->exists()
        ) {
            Schema::table('deliveries', function (Blueprint $table): void {
                $table->string('recipient_name', 150)->nullable(false)->change();
                $table->string('recipient_phone', 30)->nullable(false)->change();
                $table->text('address')->nullable(false)->change();
            });
        }
    }
};
