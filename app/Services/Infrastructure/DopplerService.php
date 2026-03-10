<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Doppler Secrets Manager (2026 DevOps Standard).
 * This service ensures that the application pulls all secrets from Doppler
 * and injects them into the Laravel config, bypassing the need for .env files.
 */
final class DopplerService
{
    /**
     * Map of Laravel config keys to Doppler (Environment) variable names.
     */
    private const SECRET_MAP = [
        'app.name' => 'APP_NAME',
        'app.env' => 'APP_ENV',
        'app.debug' => 'APP_DEBUG',
        'app.url' => 'APP_URL',
        'app.key' => 'APP_KEY',
        'app.timezone' => 'APP_TIMEZONE',
        'app.locale' => 'APP_LOCALE',

        'database.default' => 'DB_CONNECTION',
        'database.connections.mysql.host' => 'DB_HOST',
        'database.connections.mysql.port' => 'DB_PORT',
        'database.connections.mysql.database' => 'DB_DATABASE',
        'database.connections.mysql.username' => 'DB_USERNAME',
        'database.connections.mysql.password' => 'DB_PASSWORD',

        'database.connections.pgsql.host' => 'DB_HOST',
        'database.connections.pgsql.port' => 'DB_PORT',
        'database.connections.pgsql.database' => 'DB_DATABASE',
        'database.connections.pgsql.username' => 'DB_USERNAME',
        'database.connections.pgsql.password' => 'DB_PASSWORD',

        'cache.default' => 'CACHE_STORE',
        'cache.stores.redis.connection' => 'CACHE_REDIS_CONNECTION',

        'queue.default' => 'QUEUE_CONNECTION',

        'session.driver' => 'SESSION_DRIVER',
        'session.lifetime' => 'SESSION_LIFETIME',

        'mail.default' => 'MAIL_MAILER',
        'mail.host' => 'MAIL_HOST',
        'mail.port' => 'MAIL_PORT',
        'mail.username' => 'MAIL_USERNAME',
        'mail.password' => 'MAIL_PASSWORD',
        'mail.encryption' => 'MAIL_ENCRYPTION',
        'mail.from.address' => 'MAIL_FROM_ADDRESS',
        'mail.from.name' => 'MAIL_FROM_NAME',

        'services.stripe.key' => 'STRIPE_KEY',
        'services.stripe.secret' => 'STRIPE_SECRET',

        'database.connections.pgsql_ru.host' => 'DB_RU_HOST',
        'database.connections.pgsql_ru.port' => 'DB_RU_PORT',
        'database.connections.pgsql_ru.username' => 'DB_RU_USERNAME',
        'database.connections.pgsql_ru.password' => 'DB_RU_PASSWORD',

        'database.connections.pgsql_core.host' => 'DB_CORE_HOST',
        'database.connections.pgsql_core.port' => 'DB_CORE_PORT',
        'database.connections.pgsql_core.username' => 'DB_CORE_USERNAME',
        'database.connections.pgsql_core.password' => 'DB_CORE_PASSWORD',

        'advertising.ord.driver' => 'AD_ORD_DRIVER',
        'advertising.ord.api_key' => 'YANDEX_ORD_KEY',
        'advertising.ord.client_id' => 'AD_ORD_CLIENT_ID',

        'payments.gateways.tinkoff.terminal_id' => 'TINKOFF_TERMINAL_ID',
        'payments.gateways.tinkoff.secret_key' => 'TINKOFF_SECRET_KEY',
        'payments.gateways.tinkoff.api_url' => 'TINKOFF_API_URL',
        'payments.gateways.sber.username' => 'SBER_USERNAME',
        'payments.gateways.sber.password' => 'SBER_PASSWORD',
        'payments.gateways.sber.api_url' => 'SBER_API_URL',
        'payments.gateways.tochka.client_id' => 'TOCHKA_CLIENT_ID',
        'payments.gateways.tochka.client_secret' => 'TOCHKA_CLIENT_SECRET',
        'payments.ofd.default' => 'OFD_DRIVER',
        'payments.ofd.tensor.app_id' => 'TENSOR_APP_ID',
        'payments.ofd.tensor.app_secret' => 'TENSOR_APP_SECRET',
        'payments.ofd.atol.login' => 'ATOL_LOGIN',
        'payments.ofd.atol.password' => 'ATOL_PASSWORD',
    ];

    /**
     * Get a secret from environment variables (Doppler).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // Try to get from environment first (Doppler injected via CLI/SDK)
        $value = getenv($key);

        if ($value === null || $value === false) {
            return $default;
        }

        return match (strtolower((string)$value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $value,
        };
    }

    /**
     * Inject all mapped secrets into Laravel's global config.
     */
    public function boot(): void
    {
        // Не используем config() или Config::set() здесь, так как они могут быть еще не готовы
        // Вместо этого прописываем напрямую в $_ENV/$_SERVER, если значений там еще нет
        foreach (self::SECRET_MAP as $configKey => $envKey) {
            $value = $this->get($envKey);
            if ($value !== null) {
                // Это заставит Laravel увидеть эти переменные при загрузке конфигов
                if (!isset($_ENV[$envKey])) {
                    $_ENV[$envKey] = $value;
                    putenv("{$envKey}={$value}");
                }
            }
        }
    }
}
