<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use Illuminate\Support\Facades\Config;

/**
 * 2026 Multi-Zone Data Localization Service.
 * Compliance: FZ-152 (Russia) and GDPR (Global).
 */
final class DataLocalizationService
{
    /**
     * Determine storage zone for specific data type.
     * RU-ZONE: Users (PII), Payments (OFD), Auth Logs.
     * CORE-ZONE: AI Embeddings, Inventory, Chat History (non-PII).
     */
    public function getZoneForDataType(string $type): string
    {
        $ruTypes = ['user_pii', 'payment_tx', 'fiscal_record', 'auth_log'];
        
        return in_array($type, $ruTypes) ? 'ru-core' : 'app-core';
    }

    /**
     * Connection selection logic for Eloquent.
     */
    public function getConnection(string $dataType): string
    {
        $zone = $this->getZoneForDataType($dataType);
        
        return $zone === 'ru-core' ? 'pgsql_ru' : 'pgsql_core';
    }

    /**
     * Dynamic Config Injection for Multi-Zone.
     */
    public function bootMultiZoneConfig(): void
    {
        // RU-ZONE: Yandex Cloud / Selectel
        Config::set('database.connections.pgsql_ru', [
            'driver' => 'pgsql',
            'host' => DopplerService::get('DB_RU_HOST', 'ru-db.internal'),
            'port' => (int) DopplerService::get('DB_RU_PORT', 5432),
            'database' => 'pdn_storage',
            'username' => DopplerService::get('DB_RU_USERNAME'),
            'password' => DopplerService::get('DB_RU_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'require',
        ]);

        // CORE-ZONE: Hetzner / Oracle
        Config::set('database.connections.pgsql_core', [
            'driver' => 'pgsql',
            'host' => DopplerService::get('DB_CORE_HOST', 'core-db.internal'),
            'port' => (int) DopplerService::get('DB_CORE_PORT', 5432),
            'database' => 'business_logic',
            'username' => DopplerService::get('DB_CORE_USERNAME'),
            'password' => DopplerService::get('DB_CORE_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ]);
    }
}
