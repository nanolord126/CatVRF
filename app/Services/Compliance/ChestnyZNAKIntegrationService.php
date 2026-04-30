<?php

declare(strict_types=1);

namespace App\Services\Compliance;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Честный ЗНАК Integration Service - ФЗ-61 Compliance
 *
 * Integrates with Russian marking system for medicines:
 * - Product marking (Data Matrix codes)
 * - Track & trace
 * - Withdrawal from circulation
 * - Reporting to government system
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class ChestnyZNAKIntegrationService
{
    use WithAuditLogging;

    private const API_BASE_URL = 'https://api.crpt.ru/api/v3';
    private const TOKEN_CACHE_KEY = 'chestnyznak:token';
    private const TOKEN_CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Register product in Честный ЗНАК system
     *
     * @param  int  $productId  Product ID
     * @param  string  $gtin  GTIN (Global Trade Item Number)
     * @param  string  $series  Product series
     * @param  int  $tenantId  Tenant ID
     * @param  int  $userId  User registering
     * @return array Registration result
     */
    public function registerProduct(
        int $productId,
        string $gtin,
        string $series,
        int $tenantId,
        int $userId
    ): array {
        $token = $this->getAuthToken();

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post(self::API_BASE_URL.'/lk/documents/create', [
                    'product_document' => [
                        'doc_type' => 'LP_INTRODUCE_GOODS',
                        'product_group' => 'medicine',
                        'content' => [
                            'products' => [
                                [
                                    'gtin' => $gtin,
                                    'serial_number' => $series,
                                    'certificate' => $this->getProductCertificate($productId),
                                ],
                            ],
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "Честный ЗНАК API error: {$response->status()} - {$response->body()}"
                );
            }

            $result = $response->json();

            // Store registration in database
            $this->db->table('chestnyznak_registrations')->insert([
                'id' => Str::uuid()->toString(),
                'product_id' => $productId,
                'gtin' => $gtin,
                'series' => $series,
                'document_id' => $result['document_id'] ?? null,
                'status' => 'registered',
                'tenant_id' => $tenantId,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $this->logAction(
                action: 'chestnyznak_product_registered',
                entityType: 'ChestnyZNAKRegistration',
                entityId: $result['document_id'] ?? '',
                context: [
                    'product_id' => $productId,
                    'gtin' => $gtin,
                    'series' => $series,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to register product in Честный ЗНАК', [
                'product_id' => $productId,
                'gtin' => $gtin,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Verify product code in Честный ЗНАК system
     *
     * @param  string  $code  Data Matrix code
     * @return array Verification result
     */
    public function verifyCode(string $code): array
    {
        $token = $this->getAuthToken();

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post(self::API_BASE_URL.'/auth/certification/check', [
                    'code' => $code,
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "Честный ЗНАК verification error: {$response->status()}"
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logger->error('Failed to verify code in Честный ЗНАК', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Withdraw product from circulation
     *
     * @param  string  $code  Data Matrix code
     * @param  string  $reason  Withdrawal reason
     * @param  int  $tenantId  Tenant ID
     * @param  int  $userId  User withdrawing
     * @return array Withdrawal result
     */
    public function withdrawFromCirculation(
        string $code,
        string $reason,
        int $tenantId,
        int $userId
    ): array {
        $token = $this->getAuthToken();

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post(self::API_BASE_URL.'/lk/documents/create', [
                    'product_document' => [
                        'doc_type' => 'LP_INTRODUCE_GOODS',
                        'product_group' => 'medicine',
                        'content' => [
                            'action' => 'withdraw',
                            'codes' => [$code],
                            'reason' => $reason,
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "Честный ЗНАК withdrawal error: {$response->status()}"
                );
            }

            $result = $response->json();

            $this->logAction(
                action: 'chestnyznak_withdrawal',
                entityType: 'ChestnyZNAKWithdrawal',
                entityId: $result['document_id'] ?? '',
                context: [
                    'code' => $code,
                    'reason' => $reason,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to withdraw from circulation', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get product movement history from Честный ЗНАК
     *
     * @param  string  $gtin  GTIN
     * @param  string  $series  Series
     * @return array Movement history
     */
    public function getProductHistory(string $gtin, string $series): array
    {
        $token = $this->getAuthToken();

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->get(self::API_BASE_URL.'/lk/documents/history', [
                    'gtin' => $gtin,
                    'serial' => $series,
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "Честный ЗНАК history error: {$response->status()}"
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logger->error('Failed to get product history', [
                'gtin' => $gtin,
                'series' => $series,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Generate aggregated report for government submission
     *
     * @param  int  $tenantId  Tenant ID
     * @param  string  $startDate  Start date
     * @param  string  $endDate  End date
     * @return array Report data
     */
    public function generateReport(int $tenantId, string $startDate, string $endDate): array
    {
        $movements = $this->db->table('stock_movements')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('inventoryItem', function ($query) {
                $query->where('requires_marking', true);
            })
            ->get();

        $report = [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'total_movements' => $movements->count(),
            'movements_by_type' => $movements->groupBy('type')->map->count(),
            'products' => $movements->pluck('inventory_item_id')->unique()->count(),
        ];

        return $report;
    }

    /**
     * Get authentication token from Честный ЗНАК
     *
     * @return string
     */
    private function getAuthToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, self::TOKEN_CACHE_TTL, function () {
            $response = Http::timeout(30)->post(self::API_BASE_URL.'/auth/token', [
                'client_id' => config('services.chestnyznak.client_id'),
                'client_secret' => config('services.chestnyznak.client_secret'),
                'grant_type' => 'client_credentials',
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "Failed to get Честный ЗНАK auth token: {$response->status()}"
                );
            }

            return $response->json()['access_token'];
        });
    }

    /**
     * Get product certificate
     *
     * @param  int  $productId  Product ID
     * @return string
     */
    private function getProductCertificate(int $productId): string
    {
        $product = $this->db->table('products')->where('id', $productId)->first();

        return $product ? $product->certificate_number ?? '' : '';
    }
}
