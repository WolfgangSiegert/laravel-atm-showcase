<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->unsignedBigInteger('session_version')->default(0);
        });
        Schema::create('demo_reset_state', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->timestamp('last_reset_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_reset_state');
        Schema::table('cards', fn (Blueprint $table) => $table->dropColumn('session_version'));
    }
};
