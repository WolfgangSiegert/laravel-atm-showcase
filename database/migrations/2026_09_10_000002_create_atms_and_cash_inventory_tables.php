<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atms', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->char('currency', 3)->default('EUR');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('cash_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atm_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('denomination_minor');
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['atm_id', 'denomination_minor']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('atm_id')->nullable()->after('card_id')->constrained()->restrictOnDelete();
            $table->json('cash_breakdown')->nullable()->after('balance_after_minor');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('atm_id');
            $table->dropColumn('cash_breakdown');
        });

        Schema::dropIfExists('cash_inventories');
        Schema::dropIfExists('atms');
    }
};
