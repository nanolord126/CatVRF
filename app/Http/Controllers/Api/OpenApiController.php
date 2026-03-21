<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * OpenApiController — OpenAPI спецификация.
 */
final class OpenApiController extends Controller
{
    /**
     * Возвращает OpenAPI 3.0 спецификацию.
     */
    public function specification(): JsonResponse
    {
        return response()->json([
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
}
