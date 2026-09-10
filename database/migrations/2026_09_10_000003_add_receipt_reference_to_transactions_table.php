<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('receipt_reference', 30)->nullable()->after('id')->unique();
        });

        DB::table('transactions')->orderBy('id')->each(function (object $transaction): void {
            DB::table('transactions')->where('id', $transaction->id)->update([
                'receipt_reference' => 'ATM-'.Str::ulid(),
            ]);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('receipt_reference', 30)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['receipt_reference']);
            $table->dropColumn('receipt_reference');
        });
    }
};
