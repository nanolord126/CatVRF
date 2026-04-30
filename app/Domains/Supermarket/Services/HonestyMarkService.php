<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Services;

use App\Domains\Supermarket\Models\Certificate;
use App\Domains\Supermarket\Models\ProductMark;
use App\Domains\Supermarket\Exceptions\HonestyMarkException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class HonestyMarkService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('honestysign.api_url'),
            'headers' => [
                'Authorization' => 'Bearer ' . config('honestysign.token'),
                'Content-Type'  => 'application/json',
            ],
            'timeout' => config('honestysign.timeout', 30),
        ]);
    }

    /**
     * Проверка кода при добавлении товара продавцом
     */
    public function validateMark(string $dataMatrix, string $gtin): array
    {
        $cacheKey = "honesty_mark:validate:{$dataMatrix}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($dataMatrix, $gtin) {
            try {
                $response = $this->client->post('/api/v3/facade/mark/check', [
                    'json' => [
                        'mark' => $dataMatrix,
                        'gtin' => $gtin,
                    ],
                ]);

                $result = json_decode($response->getBody(), true);

                $isValid = $result['valid'] ?? false;
                $status = $result['status'] ?? 'unknown';
                $error = $result['error'] ?? null;

                Log::info('Honesty Mark validation', [
                    'data_matrix' => $dataMatrix,
                    'gtin' => $gtin,
                    'valid' => $isValid,
                    'status' => $status,
                ]);

                return [
                    'valid' => $isValid,
                    'status' => $status,
                    'error' => $error,
                ];
            } catch (\Exception $e) {
                Log::error('Honesty Mark validation failed', [
                    'mark' => $dataMatrix,
                    'gtin' => $gtin,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'valid' => false,
                    'error' => 'Сервис недоступен: ' . $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Вывод товара из оборота при продаже
     */
    public function withdraw(ProductMark $mark, array $orderItem): bool
    {
        try {
            $response = $this->client->post('/api/v3/facade/mark/withdrawal', [
                'json' => [
                    'document_type' => 'SALES_RECEIPT',
                    'products' => [
                        [
                            'mark' => $mark->data_matrix,
                            'quantity' => $orderItem['quantity'],
                            'cost' => $orderItem['price'],
                        ],
                    ],
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $mark->markAsWithdrawn();

                Log::info('Honesty Mark withdrawal successful', [
                    'mark_id' => $mark->id,
                    'data_matrix' => $mark->data_matrix,
                ]);

                return true;
            }

            Log::error('Honesty Mark withdrawal failed', [
                'mark_id' => $mark->id,
                'status' => $response->getStatusCode(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Honesty Mark withdrawal exception', [
                'mark_id' => $mark->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Создание записи о маркировке товара
     */
    public function createProductMark(array $data): ProductMark
    {
        $validation = $this->validateMark($data['data_matrix'], $data['gtin']);

        if (!$validation['valid']) {
            throw new HonestyMarkException($validation['error'] ?? 'Invalid mark');
        }

        return ProductMark::create([
            'product_id' => $data['product_id'],
            'gtin' => $data['gtin'],
            'data_matrix' => $data['data_matrix'],
            'status' => 'in_circulation',
            'introduced_at' => now(),
            'batch_number' => $data['batch_number'] ?? null,
            'production_date' => $data['production_date'] ?? null,
            'expiration_date' => $data['expiration_date'] ?? null,
            'tenant_id' => $data['tenant_id'] ?? tenant()?->id,
        ]);
    }

    /**
     * Проверка сертификата
     */
    public function validateCertificate(string $certificateNumber): array
    {
        try {
            $response = $this->client->post('/api/v3/facade/certificate/check', [
                'json' => [
                    'certificate_number' => $certificateNumber,
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            return [
                'valid' => $result['valid'] ?? false,
                'status' => $result['status'] ?? 'unknown',
                'valid_until' => $result['valid_until'] ?? null,
                'error' => $result['error'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Certificate validation failed', [
                'certificate_number' => $certificateNumber,
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'error' => 'Сервис недоступен',
            ];
        }
    }

    /**
     * Создание записи о сертификате
     */
    public function createCertificate(array $data): Certificate
    {
        $validation = $this->validateCertificate($data['certificate_number']);

        if (!$validation['valid']) {
            throw new HonestyMarkException($validation['error'] ?? 'Invalid certificate');
        }

        return Certificate::create([
            'product_id' => $data['product_id'],
            'certificate_number' => $data['certificate_number'],
            'type' => $data['type'] ?? 'certificate',
            'issued_by' => $data['issued_by'],
            'valid_from' => $data['valid_from'] ?? now(),
            'valid_until' => $validation['valid_until'] ?? $data['valid_until'],
            'file_path' => $data['file_path'] ?? null,
            'status' => 'active',
            'tenant_id' => $data['tenant_id'] ?? tenant()?->id,
        ]);
    }

    /**
     * Массовый вывод из оборота для заказа
     */
    public function withdrawOrderMarks(array $orderItems): array
    {
        $results = [];

        foreach ($orderItems as $item) {
            $mark = ProductMark::where('product_id', $item['product_id'])
                ->where('status', 'in_circulation')
                ->first();

            if ($mark) {
                $results[$item['product_id']] = $this->withdraw($mark, $item);
            } else {
                $results[$item['product_id']] = false;
            }
        }

        return $results;
    }
}
