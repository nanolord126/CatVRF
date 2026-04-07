<?php declare(strict_types=1);

use Illuminate\Support\Env;

return [
    'default_gateway' => Env::get('PAYMENTS_DEFAULT_GATEWAY', 'tinkoff'),

    'tinkoff' => [
        'terminal_key' => Env::get('TINKOFF_TERMINAL_KEY'),
        'secret_key' => Env::get('TINKOFF_SECRET_KEY'),
        'api_url' => rtrim((string) Env::get('TINKOFF_API_URL', 'https://securepay.tinkoff.ru/v2'), '/'),
        'notification_ip_whitelist' => Env::get('TINKOFF_IP_WHITELIST', ''),
        'webhook_token_header' => 'Token',
    ],

    'idempotency_ttl' => 86400,

    'webhook' => [
        'rate_limit' => Env::get('PAYMENTS_WEBHOOK_RATE', '30,1'),
        'signature_header' => 'Token',
    ],

    'recurring' => [
        'enabled' => Env::get('PAYMENTS_RECURRING_ENABLED', true),
    ],
];
