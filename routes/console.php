<?php

use App\Actions\ResetDemoData;
use App\Models\User;
use Database\Seeders\DemoAtmSeeder;
use Database\Seeders\DemoCustomerSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

Artisan::command('atm:demo-provision', function () {
    if (! config('demo.enabled')) {
        $this->error('PUBLIC_DEMO_ENABLED muss ausdrücklich aktiviert werden.');

        return 1;
    }
    if (app()->isProduction() && DB::connection()->getDriverName() !== 'pgsql') {
        $this->error('Die öffentliche Produktion benötigt PostgreSQL.');

        return 1;
    }
    (new DemoCustomerSeeder)->run();
    (new DemoAtmSeeder)->run();
    DB::table('demo_reset_state')->insertOrIgnore(['id' => 1, 'last_reset_at' => now()]);
    $guest = User::where('email', config('demo.admin_guest_email'))->first();
    if (config('demo.admin_guest_enabled')) {
        $guest ??= new User([
            'email' => config('demo.admin_guest_email'),
            'password' => Str::random(64),
        ]);
        $guest->fill([
            'name' => config('demo.admin_guest_name'),
            'is_operator' => true,
            'operator_role' => User::OPERATOR_ROLE_VIEWER,
        ])->save();
    } elseif ($guest?->isReadOnlyOperator()) {
        $guest->update(['is_operator' => false]);
    }
    $this->info('Demo-Karten und Automat eingerichtet; bestehende Salden bleiben erhalten.'.(config('demo.admin_guest_enabled') ? ' Read-only-Gast aktiviert.' : ' Kein öffentlicher Betreiber aktiviert.'));
})->purpose('Richtet ausschließlich die öffentlichen fiktiven Demo-Daten ein');

Artisan::command('atm:demo-reset {--force : Bestätigt das Löschen fiktiver Buchungen} {--if-due : Nur bei abgelaufenem Intervall}', function () {
    if (! config('demo.enabled') || ! $this->option('force')) {
        $this->error('Reset benötigt PUBLIC_DEMO_ENABLED=true und --force.');

        return 1;
    }
    $changed = app(ResetDemoData::class)->execute(onlyIfDue: $this->option('if-due'));
    $this->info($changed ? 'Demo zurückgesetzt; alte Kartensitzungen sind ungültig.' : 'Noch kein Reset fällig.');
})->purpose('Setzt nur bekannte Demo-Konten und ihren Automaten zurück');

Artisan::command('atm:operator-create {email}', function () {
    $email = Str::lower($this->argument('email'));
    if (! filter_var($email, FILTER_VALIDATE_EMAIL) || User::where('email', $email)->exists()) {
        $this->error('E-Mail ungültig oder Benutzer bereits vorhanden.');

        return 1;
    }
    $password = $this->secret('Neues Betreiberpasswort (mindestens 16 Zeichen)');
    if (! is_string($password) || mb_strlen($password) < 16) {
        $this->error('Das Passwort ist zu kurz.');

        return 1;
    }
    User::create([
        'name' => 'Showcase-Betrieb',
        'email' => $email,
        'password' => $password,
        'is_operator' => true,
        'operator_role' => User::OPERATOR_ROLE_SUPERADMIN,
    ]);
    $this->info('Superadmin angelegt.');
})->purpose('Legt einen Superadmin mit verdeckter Passworteingabe an');

Artisan::command('atm:deployment-check', function () {
    $checks = [
        'APP_ENV=production' => app()->isProduction(),
        'APP_DEBUG=false' => ! config('app.debug'),
        'APP_KEY gesetzt' => (bool) config('app.key'),
        'APP_URL mit HTTPS' => parse_url(config('app.url'), PHP_URL_SCHEME) === 'https',
        'SESSION_SECURE_COOKIE=true' => config('session.secure') === true,
        'SESSION_DRIVER=database' => config('session.driver') === 'database',
        'CACHE_STORE=database' => config('cache.default') === 'database',
        'DB_CONNECTION=pgsql' => config('database.default') === 'pgsql',
        'PUBLIC_DEMO_ENABLED=true' => config('demo.enabled'),
        'Sicherheitsheader aktiv' => config('security.headers_enabled'),
        'TRUSTED_PROXIES konfiguriert' => (bool) config('trustedproxy.proxies'),
    ];
    $failed = array_keys(array_filter($checks, fn ($valid) => ! $valid));
    if ($failed) {
        foreach ($failed as $name) {
            $this->error('Fehlt: '.$name);
        }

        return 1;
    }
    try {
        app('encrypter');
        DB::select('SELECT 1');
    } catch (Throwable) {
        $this->error('Anwendungsschlüssel oder Datenbankverbindung ungültig.');

        return 1;
    }
    $this->info('Produktionswerte und Datenbankverbindung geprüft; HTTPS/Proxy-Verhalten noch am Zielhost testen.');
})->purpose('Prüft Produktionswerte ohne Ausgabe von Geheimnissen');
