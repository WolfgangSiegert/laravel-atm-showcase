<?php

use App\Support\ProductionDatabaseTls;
use Illuminate\Support\ConfigurationUrlParser;

it('enforces CA and hostname verification after resolving conflicting URL options', function () {
    $configuration = ProductionDatabaseTls::secure([
        'driver' => 'pgsql',
        'url' => 'postgresql://app:encoded%40password@ep-example.neon.tech/app?sslmode=require&sslrootcert=/old/ca.pem',
        'sslmode' => 'prefer',
    ]);
    $effective = (new ConfigurationUrlParser)->parseConfiguration($configuration);

    expect($effective['sslmode'])->toBe('verify-full')
        ->and($effective['sslrootcert'])->toBe('/etc/ssl/certs/ca-certificates.crt')
        ->and($effective['host'])->toBe('ep-example.neon.tech')
        ->and($effective['password'])->toBe('encoded@password');
});
