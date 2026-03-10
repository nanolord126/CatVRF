<?php

namespace App\Domains\Advertising\Compliance\ORD;

use App\Domains\Advertising\Interfaces\OrdDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Exception;
use Throwable;

class MediaScoutOrdDriver implements OrdDriverInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://api.mediascout.ru/v1';
    private int $timeout = 30;
    private int $maxRetries = 3;

    public function __construct()
    {
        $this->apiKey = config('services.mediascout_ord.key', '');
        if (!$this->apiKey) {
            throw new Exception('MediaScout API key not configured');
        }
    }

    /**
     * Регистрация договора в ОРД с повторными попытками.
     * 
     * @param array<string, mixed> $data Данные договора (type, number, date, client_inn, contractor_inn)
     * @return string ID договора в ОРД
     * @throws Exception При критической ошибке API
     */
    public function createContract(array $data): string
    {
        $this->validateContractData($data);

        return $this->retryRequest(function() use ($data) {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/contracts", [
                    'type' => $data['type'],
                    'number' => $data['number'],
                    'date' => $data['date'],
                    'client_inn' => $data['client_inn'],
                    'contractor_inn' => $data['contractor_inn'],
                ]);

            if ($response->failed()) {
                Log::warning('MediaScout contract creation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'data' => $data,
                ]);
                throw new Exception("MediaScout API error: {$response->status()}");
            }

            $contractId = $response->json('id');
            if (!$contractId) {
                throw new Exception("MediaScout: No contract ID in response");
            }

            Log::info('MediaScout contract created', [
                'contract_id' => $contractId,
                'contract_number' => $data['number'],
            ]);

            return $contractId;
        });
    }

    /**
     * Регистрация рекламного креатива и получение ERID.
     * 
     * @param array<string, mixed> $data Данные креатива (type, media_url, target_url, contract_id, description)
     * @return string ERID (уникальный идентификатор рекламного материала)
     * @throws Exception При критической ошибке API
     */
    public function registerCreative(array $data): string
    {
        $this->validateCreativeData($data);

        return $this->retryRequest(function() use ($data) {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/creatives", [
                    'type' => $data['type'],
                    'media_url' => $data['media_url'],
                    'target_url' => $data['target_url'],
                    'contract_id' => $data['contract_id'],
                    'description' => $data['description'] ?? 'Ad Banner',
                ]);

            if ($response->failed()) {
                Log::warning('MediaScout creative registration failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'contract_id' => $data['contract_id'],
                ]);
                throw new Exception("MediaScout creative registration failed: {$response->status()}");
            }

            $erid = $response->json('erid');
            if (!$erid) {
                throw new Exception("MediaScout: No ERID in response");
            }

            Log::info('MediaScout creative registered', [
                'erid' => $erid,
                'contract_id' => $data['contract_id'],
                'type' => $data['type'],
            ]);

            return $erid;
        });
    }

    /**
     * Подача ежемесячной статистики в ЕРИР.
     * 
     * @param array<string, mixed> $stats Статистика (impressions, clicks, conversions, spend, etc.)
     * @throws Exception При критической ошибке API
     */
    public function pushStats(array $stats): void
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/statistics", $stats);

            if ($response->failed()) {
                Log::error('MediaScout statistics push failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception("Failed to push statistics to ORD");
            }

            Log::info('MediaScout statistics pushed successfully', [
                'impressions' => $stats['impressions'] ?? 0,
                'clicks' => $stats['clicks'] ?? 0,
            ]);

        } catch (Throwable $e) {
            Log::error('Exception in MediaScout pushStats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception("Statistics push failed: {$e->getMessage()}");
        }
    }

    /**
     * Повторная попытка при временных ошибках (429, 500, 503).
     */
    private function retryRequest(callable $request): mixed
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return $request();
            } catch (Throwable $e) {
                $lastException = $e;
                if ($attempt < $this->maxRetries) {
                    $backoff = min(pow(2, $attempt - 1) * 1000, 5000); // Exponential backoff
                    Log::debug("MediaScout retry attempt {$attempt}/{$this->maxRetries}, waiting {$backoff}ms");
                    usleep($backoff * 1000);
                }
            }
        }

        throw $lastException ?? new Exception("All retry attempts failed");
    }

    /**
     * Валидация данных договора.
     */
    private function validateContractData(array $data): void
    {
        $required = ['type', 'number', 'date', 'client_inn', 'contractor_inn'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Contract data missing required field: {$field}");
            }
        }
    }

    /**
     * Валидация данных креатива.
     */
    private function validateCreativeData(array $data): void
    {
        $required = ['type', 'media_url', 'target_url', 'contract_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Creative data missing required field: {$field}");
            }
        }
    }
}
