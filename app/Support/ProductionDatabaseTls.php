<?php

namespace App\Support;

use Illuminate\Support\ConfigurationUrlParser;

final class ProductionDatabaseTls
{
    public static function secure(array $configuration): array
    {
        // Resolve URL options first: they otherwise override connection fields.
        $configuration = (new ConfigurationUrlParser)->parseConfiguration($configuration);
        unset($configuration['url']);
        $configuration['sslmode'] = 'verify-full';
        $configuration['sslrootcert'] = '/etc/ssl/certs/ca-certificates.crt';

        return $configuration;
    }
}
