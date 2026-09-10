<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('card_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type');
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->unsignedBigInteger('balance_after_minor');
            $table->string('idempotency_key');
            $table->timestamp('created_at');
            $table->unique(['account_id', 'idempotency_key']);
            $table->index(['account_id', 'id']);
        });

        // Preserve any pre-existing balance as an explicit opening entry.
        DB::table('accounts')->where('balance_minor', '>', 0)->orderBy('id')->each(function ($account) {
            DB::table('transactions')->insert([
                'account_id' => $account->id,
                'card_id' => null,
                'type' => 'opening',
                'amount_minor' => $account->balance_minor,
                'currency' => $account->currency,
                'balance_after_minor' => $account->balance_minor,
                'idempotency_key' => 'opening:'.$account->id,
                'created_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
