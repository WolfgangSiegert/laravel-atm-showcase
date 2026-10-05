<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('showcase_traffic_daily', function (Blueprint $table): void {
            $table->date('visit_date');
            $table->string('site', 32);
            $table->string('path', 64);
            $table->unsignedBigInteger('view_count')->default(0);

            $table->primary(['visit_date', 'site', 'path']);
            $table->index(['site', 'visit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showcase_traffic_daily');
    }
};
