<?php

namespace Database\Seeders;

use App\Models\Atm;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoAtmSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo-Seeding ist nur lokal oder in Tests erlaubt.');
        }

        DB::transaction(function () {
            $atm = Atm::firstOrCreate(['code' => config('atm.code')], [
                'label' => 'Berlin Lern-Automat',
                'currency' => 'EUR',
                'status' => 'active',
            ]);

            foreach (config('atm.initial_cash_quantities') as $denomination => $quantity) {
                $atm->cashInventories()->firstOrCreate(
                    ['denomination_minor' => $denomination],
                    ['quantity' => $quantity],
                );
            }
        });
    }
}
