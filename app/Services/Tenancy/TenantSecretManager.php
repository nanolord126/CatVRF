<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Канон 2026: Динамическое управление секретами тенантов через Doppler.
 * Этот сервис позволяет переопределять конфигурацию тенанта (API ключи, мерчанты)
 * на основе данных, хранящихся в базе данных тенанта, но инжектируемых централизованно.
 */
class TenantSecretManager
{
    /**
     * Инициализация специфичных для тенанта секретов.
     * Вызывается при переключении контекста тенанта.
     */
    public function bootstrap(Tenant $tenant): void
    {
        $correlationId = request()->header('X-Correlation-ID', bin2hex(random_bytes(16)));

        // 1. Настройка платежных шлюзов (Tinkoff/Atol) для конкретного отеля/клиники
        if ($tenant->data['tinkoff_terminal_id'] ?? null) {
            Config::set('payments.gateways.tinkoff.terminal_id', $tenant->data['tinkoff_terminal_id']);
            Config::set('payments.gateways.tinkoff.secret_key', $tenant->data['tinkoff_secret_key']);
        }

        if ($tenant->data['atol_login'] ?? null) {
            Config::set('payments.ofd.atol.login', $tenant->data['atol_login']);
            Config::set('payments.ofd.atol.password', $tenant->data['atol_password']);
        }

        // 2. Логирование переключения контекста для аудита (Канон 2026)
        Log::info("Tenant context bootstrapped for: {$tenant->id}", [
            'tenant_id' => $tenant->id,
            'correlation_id' => $correlationId,
            'action' => 'secrets_injection'
        ]);
    }
}
