<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer_transactions', function (Blueprint $table): void {
            $table->string('source_key', 150)->nullable()->unique()->after('meta');
        });
    }

    public function down(): void
    {
        Schema::table('customer_transactions', function (Blueprint $table): void {
            $table->dropUnique(['source_key']);
            $table->dropColumn('source_key');
        });
    }
};
