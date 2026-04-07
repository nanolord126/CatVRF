<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;

final class OpenApiController extends Controller
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
        private readonly ResponseFactory $response,
    ) {}


    /**
         * Возвращает OpenAPI 3.0 спецификацию.
         */
        public function specification(): JsonResponse
        {
            return $this->response->json([
                'openapi' => '3.0.0',
                'info' => [
                    'title' => 'CatVRF Marketplace API',
                    'description' => 'Production-ready API для управления платежами, кошельком, промо-кампаниями и рефералами',
                    'version' => '1.0.0',
                    'contact' => [
                        'name' => 'API Support',
                        'email' => 'support@catvrf.ru',
                    ],
                    'license' => [
                        'name' => 'MIT',
                    ],
                ],
                'servers' => [
                    [
                        'url' => env('API_URL', 'https://api.catvrf.ru'),
                        'description' => 'Production',
                    ],
                    [
                        'url' => 'https://staging.catvrf.ru',
                        'description' => 'Staging',
                    ],
                ],
                'paths' => [
                    '/api/v1/payments' => [
                        'post' => [
                            'summary' => 'Инициировать платёж',
                            'operationId' => 'createPayment',
                            'tags' => ['Payments'],
                            'security' => [
                                ['bearerAuth' => []],
                            ],
                            'requestBody' => [
                                'required' => true,
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'required' => ['amount', 'currency', 'description'],
                                            'properties' => [
                                                'amount' => [
                                                    'type' => 'integer',
                                                    'description' => 'Сумма в копейках (100-50000000)',
                                                    'example' => 10000,
                                                ],
                                                'currency' => [
                                                    'type' => 'string',
                                                    'enum' => ['RUB', 'USD', 'EUR'],
                                                    'example' => 'RUB',
                                                ],
                                                'description' => [
                                                    'type' => 'string',
                                                    'maxLength' => 255,
                                                    'example' => 'Оплата заказа #123',
                                                ],
                                                'idempotency_key' => [
                                                    'type' => 'string',
                                                    'format' => 'uuid',
                                                    'description' => 'Уникальный ключ для предотвращения дублирования',
                                                ],
                                                'hold' => [
                                                    'type' => 'boolean',
                                                    'description' => 'Холд деньги без списания',
                                                    'default' => false,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'responses' => [
                                '201' => [
                                    'description' => 'Платёж создан',
                                    'content' => [
                                        'application/json' => [
                                            'schema' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'id' => ['type' => 'string', 'format' => 'uuid'],
                                                    'status' => ['type' => 'string', 'enum' => ['pending', 'authorized', 'captured']],
                                                    'amount' => ['type' => 'integer'],
                                                    'currency' => ['type' => 'string'],
                                                    'correlation_id' => ['type' => 'string', 'format' => 'uuid'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                '409' => [
                                    'description' => 'Дубликат платежа (idempotency_key уже использовался)',
                                ],
                                '429' => [
                                    'description' => 'Слишком много запросов',
                                    'headers' => [
                                        'Retry-After' => [
                                            'schema' => ['type' => 'integer'],
                                            'description' => 'Секунд до следующего запроса',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    '/api/v1/promos/apply' => [
                        'post' => [
                            'summary' => 'Применить промо-код',
                            'operationId' => 'applyPromo',
                            'tags' => ['Promos'],
                            'security' => [
                                ['bearerAuth' => []],
                            ],
                            'requestBody' => [
                                'required' => true,
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'required' => ['promo_code'],
                                            'properties' => [
                                                'promo_code' => [
                                                    'type' => 'string',
                                                    'pattern' => '^[A-Z0-9_-]{3,50}$',
                                                    'example' => 'SUMMER2024',
                                                ],
                                                'cart_id' => ['type' => 'string', 'format' => 'uuid'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'responses' => [
                                '200' => [
                                    'description' => 'Промо применён',
                                ],
                                '404' => [
                                    'description' => 'Промо-код не найден',
                                ],
                                '429' => [
                                    'description' => 'Слишком много попыток применения',
                                ],
                            ],
                        ],
                    ],
                ],
                'components' => [
                    'securitySchemes' => [
                        'bearerAuth' => [
                            'type' => 'http',
                            'scheme' => 'bearer',
                            'bearerFormat' => 'JWT',
                            'description' => 'Используйте Sanctum токен',
                        ],
                        'apiKey' => [
                            'type' => 'apiKey',
                            'in' => 'header',
                            'name' => 'X-API-Key',
                            'description' => 'B2B API ключ',
                        ],
                    ],
                    'schemas' => [
                        'ErrorResponse' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => ['type' => 'string'],
                                'correlation_id' => ['type' => 'string', 'format' => 'uuid'],
                                'errors' => ['type' => 'object'],
                            ],
                        ],
                    ],
                ],
                'tags' => [
                    [
                        'name' => 'Payments',
                        'description' => 'Управление платежами',
                    ],
                    [
                        'name' => 'Promos',
                        'description' => 'Управление промо-кодами',
                    ],
                    [
                        'name' => 'Wallets',
                        'description' => 'Управление кошельком',
                    ],
                ],
            ]);
        }
        /**
         * UI для Swagger.
         */
        public function ui()
        {
            return view('api.swagger-ui', [
                'specUrl' => route('api.openapi.spec'),
            ]);
        }
        /**
         * Postman коллекция
         */
        public function postman(): \Illuminate\Http\Response
        {
            $collection = [
                'info' => [
                    'name' => 'CatVRF Marketplace API',
                    'description' => 'Production-ready API для управления платежами, кошельком, промо и рефералами',
                    'version' => '1.0.0',
                    'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
                ],
                'item' => [
                    // Payments
                    [
                        'name' => 'Payments',
                        'item' => [
                            [
                                'name' => 'Initialize Payment',
                                'request' => [
                                    'method' => 'POST',
                                    'header' => [
                                        ['key' => 'Authorization', 'value' => 'Bearer {{token}}'],
                                        ['key' => 'X-Correlation-ID', 'value' => '{{correlation_id}}'],
                                    ],
                                    'url' => [
                                        'raw' => '{{base_url}}/api/v1/payments',
                                        'protocol' => 'https',
                                        'host' => ['{{base_url}}'],
                                        'path' => ['api', 'v1', 'payments'],
                                    ],
                                    'body' => [
                                        'mode' => 'raw',
                                        'raw' => json_encode([
                                            'order_id' => 123,
                                            'amount' => 50000,
                                            'currency' => 'RUB',
                                            'description' => 'Order payment',
                                        ]),
                                    ],
                                ],
                            ],
                            [
                                'name' => 'Get Payment Status',
                                'request' => [
                                    'method' => 'GET',
                                    'header' => [
                                        ['key' => 'Authorization', 'value' => 'Bearer {{token}}'],
                                    ],
                                    'url' => [
                                        'raw' => '{{base_url}}/api/v1/payments/{{payment_id}}',
                                        'host' => ['{{base_url}}'],
                                        'path' => ['api', 'v1', 'payments', '{{payment_id}}'],
                                    ],
                                ],
                            ],
                            [
                                'name' => 'Refund Payment',
                                'request' => [
                                    'method' => 'POST',
                                    'header' => [
                                        ['key' => 'Authorization', 'value' => 'Bearer {{token}}'],
                                    ],
                                    'url' => [
                                        'raw' => '{{base_url}}/api/v1/payments/{{payment_id}}/refund',
                                        'host' => ['{{base_url}}'],
                                        'path' => ['api', 'v1', 'payments', '{{payment_id}}', 'refund'],
                                    ],
                                    'body' => [
                                        'mode' => 'raw',
                                        'raw' => json_encode(['amount' => 50000]),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Wallets
                    [
                        'name' => 'Wallets',
                        'item' => [
                            [
                                'name' => 'Get Wallet Balance',
                                'request' => [
                                    'method' => 'GET',
                                    'header' => [
                                        ['key' => 'Authorization', 'value' => 'Bearer {{token}}'],
                                    ],
                                    'url' => [
                                        'raw' => '{{base_url}}/api/v1/wallets',
                                        'host' => ['{{base_url}}'],
                                        'path' => ['api', 'v1', 'wallets'],
                                    ],
                                ],
                            ],
                            [
                                'name' => 'Deposit to Wallet',
                                'request' => [
                                    'method' => 'POST',
                                    'header' => [
                                        ['key' => 'Authorization', 'value' => 'Bearer {{token}}'],
                                    ],
                                    'url' => [
                                        'raw' => '{{base_url}}/api/v1/wallets/{{wallet_id}}/deposit',
                                        'host' => ['{{base_url}}'],
                                        'path' => ['api', 'v1', 'wallets', '{{wallet_id}}', 'deposit'],
                                    ],
                                    'body' => [
                                        'mode' => 'raw',
                                        'raw' => json_encode(['amount' => 100000]),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Promo
                    [
                        'name' => 'Promo',
                        'item' => [
                            [
                                'name' => 'Apply Promo Code',
                                'request' => [
                                    'method' => 'POST',
                                    'header' => [
                                        ['key' => 'Authorization', 'value' => 'Bearer {{token}}'],
                                    ],
                                    'url' => [
                                        'raw' => '{{base_url}}/api/v1/promos/apply',
                                        'host' => ['{{base_url}}'],
                                        'path' => ['api', 'v1', 'promos', 'apply'],
                                    ],
                                    'body' => [
                                        'mode' => 'raw',
                                        'raw' => json_encode(['code' => 'PROMO2024', 'amount' => 50000]),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Search
                    [
                        'name' => 'Search',
                        'item' => [
                            [
                                'name' => 'Global Search',
                                'request' => [
                                    'method' => 'GET',
                                    'header' => [],
                                    'url' => [
                                        'raw' => '{{base_url}}/api/v1/search?q=салон красоты&vertical=beauty',
                                        'host' => ['{{base_url}}'],
                                        'path' => ['api', 'v1', 'search'],
                                        'query' => [
                                            ['key' => 'q', 'value' => 'салон красоты'],
                                            ['key' => 'vertical', 'value' => 'beauty'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'variable' => [
                    ['key' => 'base_url', 'value' => env('API_URL', 'https://api.catvrf.ru')],
                    ['key' => 'token', 'value' => ''],
                    ['key' => 'correlation_id', 'value' => ''],
                ],
            ];
            return $this->responseFactory
                ->json($collection)
                ->header('Content-Disposition', 'attachment; filename="CatVRF-Marketplace-API.postman_collection.json"');
        }
}
