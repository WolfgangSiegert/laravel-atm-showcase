<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_traffic_events', function (Blueprint $table): void {
            $table->id();
            $table->timestampTz('occurred_at')->index();
            $table->string('site', 32);
            $table->string('path', 64);
            $table->char('visitor_hash', 64);

            $table->index(['path', 'occurred_at']);
            $table->index(['visitor_hash', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_traffic_events');
    }
};
