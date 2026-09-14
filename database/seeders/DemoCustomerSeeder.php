<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Card;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoCustomerSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) && ! config('demo.enabled')) {
            throw new \RuntimeException('Demo-Seeding ist nur lokal oder in Tests erlaubt.');
        }

        DB::transaction(function () {
            foreach ([['DEMO-001', 'Alex Demo', '1234'], ['DEMO-002', 'Sam Beispiel', '0042']] as [$reference, $name, $pin]) {
                // Re-running the seeder must not reset PINs, locks or balances.
                if (Card::where('demo_reference', $reference)->exists()) {
                    continue;
                }
                $account = Account::firstOrCreate(['reference' => $reference], [
                    'customer_id' => Customer::firstOrCreate(['display_name' => $name])->id,
                    'currency' => 'EUR',
                    'balance_minor' => 0,
                ]);
                Card::create([
                    'account_id' => $account->id,
                    'demo_reference' => $reference,
                    'pin_hash' => $pin,
                ]);
            }
        });
    }
}
