<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('atms')
            ->where('code', 'BER-DEMO-01')
            ->where('label', 'Berlin Lern-Automat')
            ->update(['label' => 'LERN-Bank Mein Geldautomat']);
    }

    public function down(): void
    {
        DB::table('atms')
            ->where('code', 'BER-DEMO-01')
            ->where('label', 'LERN-Bank Mein Geldautomat')
            ->update(['label' => 'Berlin Lern-Automat']);
    }
};
