<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Normalize existing values that have a direct equivalent in the new specialty list.
        $mapping = [
            'متخصص پوست اطفال و کودکان' => 'متخصص اطفال (پدیاتریک)',
            'متخصص آلرژی و ایمنی‌شناسی' => 'متخصص ایمونولوژی و آلرژی',
            'متخصص غدد و متابولیسم' => 'متخصص غدد (اندوکرینولوژی)',
        ];

        foreach ($mapping as $old => $new) {
            DB::table('doctors')
                ->where('specialty', $old)
                ->update(['specialty' => $new]);
        }

        // The legacy phlebology value has no safe equivalent in the new list,
        // so it is intentionally preserved by the enum for historical records.
    }

    public function down(): void
    {
        $mapping = [
            'متخصص اطفال (پدیاتریک)' => 'متخصص پوست اطفال و کودکان',
            'متخصص ایمونولوژی و آلرژی' => 'متخصص آلرژی و ایمنی‌شناسی',
            'متخصص غدد (اندوکرینولوژی)' => 'متخصص غدد و متابولیسم',
        ];

        foreach ($mapping as $new => $old) {
            DB::table('doctors')
                ->where('specialty', $new)
                ->update(['specialty' => $old]);
        }
    }
};
