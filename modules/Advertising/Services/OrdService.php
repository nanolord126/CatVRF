<?php

declare(strict_types=1);

namespace Modules\Advertising\Services;

use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Log\LogManager;
use Modules\Advertising\Models\Creative;
use Modules\Common\Services\AbstractTechnicalVerticalService;

/**
 * Сервис ОРД-маркировки рекламы (ФЗ-38 «О рекламе»).
 *
 * Получает erid (Electronic Resource ID) от оператора рекламных данных.
 * Поддерживает Яндекс ОРД и fallback-режим для разработки.
 *
 * КАНОН 2026: HTTP-запросы только через внедрённый HttpClient, не static Http::
 */
final class OrdService extends AbstractTechnicalVerticalService
{
    public function __construct(
        private readonly HttpClient  $http,
        private readonly LogManager $log,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('advertising.ord.enabled', true);
    }

    /**
     * Получить erid для креатива от ОРД.
     * Вызывается ТОЛЬКО после создания Creative в транзакции.
     *
     * @throws \RuntimeException При ошибке ОРД API
     */
    public function getErid(Creative $creative): string
    {
        $correlationId = $this->getCorrelationId();
        $driver        = config('advertising.ord.driver', 'fallback');

        $this->log->channel('audit')->info('ord.erid.request', [
            'correlation_id' => $correlationId,
            'creative_id'    => $creative->id,
            'driver'         => $driver,
        ]);

        try {
            $erid = match ($driver) {
                'yandex' => $this->fetchYandexErid($creative),
                default  => 'FALLBACK-' . substr(md5($creative->id . time()), 0, 12),
            };

            $this->log->channel('audit')->info('ord.erid.success', [
                'correlation_id' => $correlationId,
                'creative_id'    => $creative->id,
                'erid'           => $erid,
            ]);

            return $erid;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('ord.erid.error', [
                'correlation_id' => $correlationId,
                'creative_id'    => $creative->id,
                'error'          => $e->getMessage(),
            ]);

            // Fallback при недоступности API — пометить как SYNC-PENDING
            return 'SYNC-PENDING-' . $creative->id;
        }
    }

    private function fetchYandexErid(Creative $creative): string
    {
        $apiKey = config('advertising.ord.api_key', '');

        $response = $this->http
            ->withToken($apiKey)
            ->timeout(5)
            ->post('https://ord.yandex.ru/api/v1/creatives', [
                'title'      => $creative->title ?? '',
                'text'       => $creative->content ?? '',
                'target_url' => $creative->link ?? '',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "Yandex ОРД error {$response->status()}: " . $response->body()
            );
        }

        return $response->json('erid') ?? throw new \RuntimeException('Yandex ОРД: erid not found in response');
    }
}

