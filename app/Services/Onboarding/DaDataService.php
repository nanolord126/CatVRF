<?php

declare(strict_types=1);

namespace App\Services\Onboarding;

use Illuminate\Support\Collection;

use App\Models\VerificationLog;
use App\Enums\VerificationType;
use App\Enums\VerificationResult;
use Illuminate\Cache\CacheManager;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;

/**
 * DaData Service for INN validation and party lookup.
 * Production-ready with caching, fraud control integration, and fallback.
 */
final readonly class DaDataService
{
    private const CACHE_TTL = 3600; // 1 hour

    private const API_URL = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party';

    private const API_URL_INN = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/party';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $secretKey,
        private readonly CacheManager $cache,
        private readonly HttpFactory $http,
        private readonly LogManager $log,
    ) {}

    /**
     * Find party by INN (full company data)
     */
    public function findParty(string $inn): array
    {
        $cacheKey = "dadata:party:{$inn}";

        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($inn) {
            try {
                $response = $this->http->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => "Token {$this->apiKey}",
                ])->timeout(10)
                ->post(self::API_URL, [
                    'query' => $inn,
                ]);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $this->log->channel('fraud_alert')->error('DaData API connection error', [
                    'inn' => $inn,
                    'error' => $e->getMessage(),
                ]);

                return ['found' => false, 'inn' => $inn, 'error' => 'connection_failed'];
            } catch (\Illuminate\Http\Client\RequestException $e) {
                $this->log->channel('fraud_alert')->error('DaData API request error', [
                    'inn' => $inn,
                    'error' => $e->getMessage(),
                ]);

                return ['found' => false, 'inn' => $inn, 'error' => 'request_failed'];
            }

            if (! $response->successful()) {
                $this->log->channel('fraud_alert')->warning('DaData API request failed', [
                    'inn' => $inn,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['found' => false, 'inn' => $inn, 'error' => 'api_failed'];
            }

            $data = $response->json();

            if (empty($data['suggestions'])) {
                return [
                    'found' => false,
                    'inn' => $inn,
                ];
            }

            $suggestion = $data['suggestions'][0];
            $party = $suggestion['data'];

            return [
                'found' => true,
                'inn' => $inn,
                'kpp' => $party['kpp'] ?? null,
                'ogrn' => $party['ogrn'] ?? null,
                'name' => [
                    'full' => $party['name']['full_with_opf'] ?? $party['name']['full'] ?? null,
                    'short' => $party['name']['short_with_opf'] ?? $party['name']['short'] ?? null,
                ],
                'legal_address' => $party['address']['unrestricted_value'] ?? null,
                'actual_address' => $party['address']['data']['unrestricted_value'] ?? null,
                'director' => [
                    'name' => $party['management']['name'] ?? null,
                    'post' => $party['management']['post'] ?? null,
                ],
                'status' => $party['state']['status'] ?? null,
                'liquidation_date' => $party['state']['liquidation_date'] ?? null,
                'type' => $party['type'] ?? null, // LEGAL, INDIVIDUAL
                'raw' => $party,
            ];
        });
    }

    /**
     * Validate INN and return simplified data for quick check
     */
    public function validateInn(string $inn): array
    {
        try {
            $party = $this->findParty($inn);

            if (! $party['found']) {
                return [
                    'valid' => false,
                    'reason' => 'ИНН не найден в базе Дадаты',
                ];
            }

            // Check if company is liquidated
            if (! empty($party['liquidation_date'])) {
                return [
                    'valid' => false,
                    'reason' => 'Компания ликвидирована',
                    'liquidation_date' => $party['liquidation_date'],
                ];
            }

            // Check status
            if (! in_array($party['status'], ['ACTIVE', 'REORGANIZING'], true)) {
                return [
                    'valid' => false,
                    'reason' => 'Компания неактивна: '.$party['status'],
                ];
            }

            return [
                'valid' => true,
                'inn' => $party['inn'],
                'name' => $party['name']['short'] ?? $party['name']['full'],
                'ogrn' => $party['ogrn'],
                'kpp' => $party['kpp'],
                'type' => $party['type'],
            ];
        } catch (\Throwable $e) {
            $this->log->channel('fraud_alert')->error('DaData validation failed', [
                'inn' => $inn,
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'reason' => 'Ошибка при проверке ИНН',
                'fallback' => true,
            ];
        }
    }

    /**
     * Check if INN belongs to the same legal entity as parent INN
     * Used for branch registration validation
     */
    public function isSameLegalEntity(string $inn, string $parentInn): bool
    {
        try {
            $party = $this->findParty($inn);
            $parentParty = $this->findParty($parentInn);

            if (! $party['found'] || ! $parentParty['found']) {
                return false;
            }

            // Check OGRN match (same legal entity)
            return $party['ogrn'] === $parentParty['ogrn'];
        } catch (\Throwable $e) {
            $this->log->channel('fraud_alert')->error('DaData same legal entity check failed', [
                'inn' => $inn,
                'parent_inn' => $parentInn,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Suggest companies by name (for autocomplete)
     */
    public function suggestByName(string $name, int $count = 5): array
    {
        $cacheKey = 'dadata:suggest:'.md5($name);

        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($name, $count) {
            $response = $this->http->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => "Token {$this->apiKey}",
            ])->post(self::API_URL_INN, [
                'query' => $name,
                'count' => $count,
            ]);

            if (! $response->successful()) {
                return [];
            }

            $data = $response->json();

            return new Collection($data['suggestions'] ?? [])
                ->map(fn ($suggestion) => [
                    'inn' => $suggestion['data']['inn'] ?? null,
                    'name' => $suggestion['value'] ?? null,
                    'ogrn' => $suggestion['data']['ogrn'] ?? null,
                    'address' => $suggestion['data']['address']['unrestricted_value'] ?? null,
                ])
                ->filter(fn ($item) => ! empty($item['inn']))
                ->values()
                ->toArray();
        });
    }

    /**
     * Log verification attempt
     */
    public function logVerification(
        ?int $userId,
        ?int $tenantId,
        ?int $businessGroupId,
        string $inn,
        array $result,
        string $correlationId = ''
    ): VerificationLog {
        return VerificationLog::create([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'business_group_id' => $businessGroupId,
            'type' => VerificationType::Inn->value,
            'provider' => 'dadata',
            'score' => $result['valid'] ? 1.0 : 0.0,
            'result' => $result['valid'] ? VerificationResult::Success->value : VerificationResult::Failed->value,
            'metadata' => array_merge($result, ['inn' => $inn]),
            'reason' => $result['reason'] ?? null,
            'correlation_id' => $correlationId,
        ]);
    }
}
