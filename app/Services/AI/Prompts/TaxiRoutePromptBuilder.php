<?php

declare(strict_types=1);

namespace App\Services\AI\Prompts;

/**
 * Prompt builder for Taxi Route Optimization AI
 * 
 * Vertical: taxi
 * Type: route_optimization
 * 
 * Generates prompts for:
 * - Route optimization
 * - Dynamic pricing
 * - Driver matching
 */
final class TaxiRoutePromptBuilder extends AbstractPromptBuilder
{
    protected string $version = '1.0.0';
    protected array $metadata = [
        'vertical' => 'taxi',
        'type' => 'route_optimization',
        'description' => 'Optimizes taxi routes, calculates dynamic pricing, and matches drivers',
        'language' => 'ru',
    ];

    public function getSystemPrompt(array $context = []): string
    {
        $prompt = <<<PROMPT
Ты — эксперт по логистике и динамическому ценообразованию в такси. 
Твоя задача — анализировать данные о поездке и предоставлять оптимизированный маршрут, рекомендованный тариф и подходящего водителя.

Контекст:
- Вертикаль: такси
- Тип оптимизации: маршрут + ценообразование + подбор водителя
- Язык ответа: русский

Правила:
1. Всегда учитывай текущую ситуацию на дорогах (пробки, погода)
2. Рассчитывай динамический тариф на основе спроса/предложения
3. Рекомендуй водителей с высоким рейтингом и близким расположением
4. Предоставляй альтернативные маршруты при необходимости
5. Оценивай время прибытия с запасом (+10-20%)
PROMPT;

        $this->logUsage('system', $context);

        return $this->sanitize($prompt);
    }

    public function getUserPrompt(array $context = []): string
    {
        $pickupLocation = $context['pickup_location'] ?? 'Не указано';
        $dropoffLocation = $context['dropoff_location'] ?? 'Не указано';
        $rideType = $context['ride_type'] ?? 'economy';
        $passengers = $context['passengers'] ?? 1;

        $prompt = <<<PROMPT
Проанализируй следующие данные о поездке:

**Пункт отправления:** {{pickup_location}}
**Пункт назначения:** {{dropoff_location}}
**Тип поездки:** {{ride_type}}
**Количество пассажиров:** {{passengers}}

Определи:
1. Оптимальный маршрут (основной и альтернативный)
2. Рекомендуемый тариф с обоснованием
3. Критерии подбора водителя
4. Оценочное время прибытия
5. Примерную стоимость поездки

Ответ предоставь в структурированном JSON формате.
PROMPT;

        $interpolated = $this->interpolate($prompt, [
            'pickup_location' => $pickupLocation,
            'dropoff_location' => $dropoffLocation,
            'ride_type' => $rideType,
            'passengers' => $passengers,
        ]);

        $this->logUsage('user', $context);

        return $this->sanitize($interpolated);
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'route' => [
                    'type' => 'object',
                    'properties' => [
                        'primary' => [
                            'type' => 'object',
                            'properties' => [
                                'description' => ['type' => 'string'],
                                'distance_km' => ['type' => 'number'],
                                'estimated_time_minutes' => ['type' => 'integer'],
                            ],
                        ],
                        'alternative' => [
                            'type' => 'object',
                            'properties' => [
                                'description' => ['type' => 'string'],
                                'distance_km' => ['type' => 'number'],
                                'estimated_time_minutes' => ['type' => 'integer'],
                            ],
                        ],
                    ],
                ],
                'pricing' => [
                    'type' => 'object',
                    'properties' => [
                        'recommended_tariff' => ['type' => 'string'],
                        'base_price' => ['type' => 'number'],
                        'dynamic_multiplier' => ['type' => 'number'],
                        'final_price' => ['type' => 'number'],
                        'currency' => ['type' => 'string'],
                        'justification' => ['type' => 'string'],
                    ],
                ],
                'driver_criteria' => [
                    'type' => 'object',
                    'properties' => [
                        'min_rating' => ['type' => 'number'],
                        'max_distance_km' => ['type' => 'number'],
                        'preferred_car_class' => ['type' => 'string'],
                        'special_requirements' => ['type' => 'array', 'items' => ['type' => 'string']],
                    ],
                ],
                'eta' => [
                    'type' => 'object',
                    'properties' => [
                        'driver_arrival_minutes' => ['type' => 'integer'],
                        'destination_arrival_minutes' => ['type' => 'integer'],
                        'total_time_minutes' => ['type' => 'integer'],
                    ],
                ],
            ],
            'required' => ['route', 'pricing', 'driver_criteria', 'eta'],
        ];
    }
}
