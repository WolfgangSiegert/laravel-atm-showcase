<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_metrics', function (Blueprint $table): void {
            $table->date('metric_date');
            $table->string('metric_name', 64);
            $table->unsignedBigInteger('count')->default(0);
            $table->primary(['metric_date', 'metric_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_metrics');
    }
};
