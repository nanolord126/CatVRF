<?php

declare(strict_types=1);

namespace App\Services\Integration;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpClient;
use Psr\Log\LoggerInterface;

/**
 * Chestny ZNAK Integration Service
 *
 * Integrates with Russian Chestny ZNAK marking system (ФЗ-61):
 * - Marking code validation
 * - Product information lookup
 * - Document submission
 * - Status tracking
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class ChestnyZNAKIntegrationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache,
        private readonly HttpClient $httpClient
    ) {}

    /**
     * Validate marking code with Chestny ZNAK API
     *
     * @param  string  $markingCode  Marking code
     * @param  int  $tenantId  Tenant ID
     * @return array Validation result
     */
    public function validateMarkingCode(string $markingCode, int $tenantId): array
    {
        $cacheKey = "chestny_znak:validate:{$markingCode}";
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = $this->httpClient->post(config('chestny_znak.api_url') . '/validate', [
                'marking_code' => $markingCode,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $result['source'] = 'api';
            } else {
                $result = [
                    'valid' => false,
                    'marking_code' => $markingCode,
                    'error' => 'API validation failed',
                    'source' => 'api',
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Chestny ZNAK API error', [
                'error' => $e->getMessage(),
                'marking_code' => $markingCode,
            ]);

            $result = $this->validateLocally($markingCode);
            $result['source'] = 'local_fallback';
        }

        $this->db->table('chestny_znak_validations')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'marking_code' => $markingCode,
            'valid' => $result['valid'] ?? false,
            'tenant_id' => $tenantId,
            'validated_at' => now(),
        ]);

        $this->cache->put($cacheKey, $result, now()->addHours(24));

        return $result;
    }

    /**
     * Validate marking code locally (fallback)
     *
     * @param  string  $markingCode  Marking code
     * @return array Validation result
     */
    private function validateLocally(string $markingCode): array
    {
        $isValid = $this->isValidChestnyZnakFormat($markingCode);

        if (! $isValid) {
            return [
                'valid' => false,
                'marking_code' => $markingCode,
                'error' => 'Invalid format',
            ];
        }

        $parts = $this->parseChestnyZnakCode($markingCode);

        return [
            'valid' => true,
            'marking_code' => $markingCode,
            'gtin' => $parts['gtin'],
            'serial' => $parts['serial'],
            'verification_code' => $parts['verification_code'],
        ];
    }

    /**
     * Get product information by GTIN
     *
     * @param  string  $gtin  GTIN
     * @param  int  $tenantId  Tenant ID
     * @return array Product information
     */
    public function getProductByGTIN(string $gtin, int $tenantId): array
    {
        $cacheKey = "chestny_znak:product:{$gtin}";
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = $this->httpClient->get(config('chestny_znak.api_url') . '/product/' . $gtin);

            if ($response->successful()) {
                $result = $response->json();
            } else {
                $result = [
                    'gtin' => $gtin,
                    'error' => 'Product not found',
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Chestny ZNAK product lookup error', [
                'error' => $e->getMessage(),
                'gtin' => $gtin,
            ]);

            $result = [
                'gtin' => $gtin,
                'error' => 'API unavailable',
            ];
        }

        $this->cache->put($cacheKey, $result, now()->addHours(24));

        return $result;
    }

    /**
     * Submit document to Chestny ZNAK
     *
     * @param  string  $documentType  Document type
     * @param  array  $documentData  Document data
     * @param  int  $tenantId  Tenant ID
     * @return array Submission result
     */
    public function submitDocument(string $documentType, array $documentData, int $tenantId): array
    {
        try {
            $response = $this->httpClient->post(config('chestny_znak.api_url') . '/documents', [
                'document_type' => $documentType,
                'data' => $documentData,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $status = 'submitted';
            } else {
                $result = [
                    'document_type' => $documentType,
                    'error' => 'Submission failed',
                    'status' => 'failed',
                ];
                $status = 'failed';
            }
        } catch (\Exception $e) {
            $this->logger->error('Chestny ZNAK document submission error', [
                'error' => $e->getMessage(),
                'document_type' => $documentType,
            ]);

            $result = [
                'document_type' => $documentType,
                'error' => $e->getMessage(),
                'status' => 'failed',
            ];
            $status = 'failed';
        }

        $this->db->table('chestny_znak_documents')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'document_type' => $documentType,
            'document_id' => $result['document_id'] ?? null,
            'data' => json_encode($documentData),
            'status' => $status,
            'tenant_id' => $tenantId,
            'submitted_at' => now(),
        ]);

        $this->logAction(
            action: 'chestny_znak_document_submitted',
            entityType: 'ChestnyZNAKDocument',
            entityId: $result['document_id'] ?? null,
            context: [
                'document_type' => $documentType,
                'status' => $status,
            ],
            userId: 0,
            tenantId: $tenantId
        );

        return $result;
    }

    /**
     * Check document status
     *
     * @param  string  $documentId  Document ID
     * @param  int  $tenantId  Tenant ID
     * @return array Document status
     */
    public function checkDocumentStatus(string $documentId, int $tenantId): array
    {
        try {
            $response = $this->httpClient->get(config('chestny_znak.api_url') . '/documents/' . $documentId);

            if ($response->successful()) {
                $result = $response->json();
            } else {
                $result = [
                    'document_id' => $documentId,
                    'error' => 'Status check failed',
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Chestny ZNAK status check error', [
                'error' => $e->getMessage(),
                'document_id' => $documentId,
            ]);

            $result = [
                'document_id' => $documentId,
                'error' => 'API unavailable',
            ];
        }

        $this->db->table('chestny_znak_documents')
            ->where('document_id', $documentId)
            ->where('tenant_id', $tenantId)
            ->update([
                'status' => $result['status'] ?? 'unknown',
                'checked_at' => now(),
            ]);

        return $result;
    }

    /**
     * Validate Chestny ZNAK format
     *
     * @param  string  $code  Marking code
     * @return bool Is valid
     */
    private function isValidChestnyZnakFormat(string $code): bool
    {
        $length = strlen($code);

        if ($length < 29 || $length > 31) {
            return false;
        }

        if (! ctype_alnum($code)) {
            return false;
        }

        return true;
    }

    /**
     * Parse Chestny ZNAK code
     *
     * @param  string  $code  Marking code
     * @return array Parsed components
     */
    private function parseChestnyZnakCode(string $code): array
    {
        return [
            'gtin' => substr($code, 0, 14),
            'serial' => substr($code, 14, 13),
            'verification_code' => substr($code, 27, 4),
        ];
    }

    /**
     * Get integration statistics
     *
     * @param  int  $tenantId  Tenant ID
     * @return array Statistics
     */
    public function getIntegrationStatistics(int $tenantId): array
    {
        $validations = $this->db->table('chestny_znak_validations')
            ->where('tenant_id', $tenantId)
            ->where('validated_at', '>=', now()->subDays(30))
            ->get();

        $documents = $this->db->table('chestny_znak_documents')
            ->where('tenant_id', $tenantId)
            ->where('submitted_at', '>=', now()->subDays(30))
            ->get();

        return [
            'tenant_id' => $tenantId,
            'period_days' => 30,
            'validations' => [
                'total' => $validations->count(),
                'valid' => $validations->where('valid', true)->count(),
                'invalid' => $validations->where('valid', false)->count(),
            ],
            'documents' => [
                'total' => $documents->count(),
                'submitted' => $documents->where('status', 'submitted')->count(),
                'failed' => $documents->where('status', 'failed')->count(),
                'completed' => $documents->where('status', 'completed')->count(),
            ],
        ];
    }

    /**
     * Sync marking codes from Chestny ZNAK
     *
     * @param  int  $batchId  Batch ID
     * @param  int  $tenantId  Tenant ID
     * @return array Sync results
     */
    public function syncMarkingCodes(int $batchId, int $tenantId): array
    {
        $batch = $this->db->table('inventory_batches')
            ->where('id', $batchId)
            ->first();

        if (! $batch) {
            throw new \RuntimeException("Batch not found: {$batchId}");
        }

        $markingCodes = $this->db->table('batch_marking_codes')
            ->where('batch_id', $batchId)
            ->pluck('marking_code')
            ->toArray();

        $results = [
            'batch_id' => $batchId,
            'total_codes' => count($markingCodes),
            'valid' => 0,
            'invalid' => 0,
            'errors' => [],
        ];

        foreach ($markingCodes as $code) {
            try {
                $validation = $this->validateMarkingCode($code, $tenantId);

                if ($validation['valid'] ?? false) {
                    $results['valid']++;
                } else {
                    $results['invalid']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'marking_code' => $code,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Check API health
     *
     * @return array Health status
     */
    public function checkApiHealth(): array
    {
        try {
            $response = $this->httpClient->get(config('chestny_znak.api_url') . '/health', [
                'timeout' => 5,
            ]);

            $isHealthy = $response->successful();

            return [
                'healthy' => $isHealthy,
                'status_code' => $response->status(),
                'response_time' => $response->handlerStats()['total_time'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
