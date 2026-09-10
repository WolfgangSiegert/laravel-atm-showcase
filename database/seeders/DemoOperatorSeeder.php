<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoOperatorSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo-Seeding ist nur lokal oder in Tests erlaubt.');
        }

        User::firstOrCreate(['email' => config('atm.demo_operator_email')], [
            'name' => 'Lokaler Demo-Betrieb',
            'password' => config('atm.demo_operator_password'),
            'is_operator' => true,
            'email_verified_at' => now(),
        ]);
    }
}
