<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_operator')->default(false)->after('password');
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type')->index();
            $table->string('outcome');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('card_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('atm_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('reason_code')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_operator');
        });
    }
};
