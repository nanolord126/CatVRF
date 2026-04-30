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
 * 1C Integration Service
 *
 * Integrates with 1C:Enterprise ERP system:
 * - Product catalog sync
 * - Inventory sync
 * - Financial documents sync
 * - Counterparty data sync
 * - Warehouse operations sync
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class OneCIntegrationService
{
    use WithAuditLogging;

    private const API_BASE_URL = 'https://1c.example.com/hs/catvrf/api';
    private const TOKEN_CACHE_KEY = 'onec:token';
    private const TOKEN_CACHE_TTL = 7200; // 2 hours

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Sync products from 1C
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $userId  User syncing
     * @return array Sync result
     */
    public function syncProducts(int $tenantId, int $userId): array
    {
        $token = $this->getAuthToken();

        try {
            $response = Http::withToken($token)
                ->timeout(120)
                ->get(self::API_BASE_URL.'/products');

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "1C API error: {$response->status()} - {$response->body()}"
                );
            }

            $products = $response->json('products', []);

            $synced = 0;
            $updated = 0;
            foreach ($products as $product) {
                $existing = $this->db->table('products')
                    ->where('onec_code', $product['code'])
                    ->where('tenant_id', $tenantId)
                    ->first();

                if ($existing) {
                    $this->db->table('products')
                        ->where('id', $existing->id)
                        ->update([
                            'name' => $product['name'],
                            'sku' => $product['sku'] ?? null,
                            'barcode' => $product['barcode'] ?? null,
                            'unit' => $product['unit'] ?? 'piece',
                            'price' => $product['price'] ?? 0,
                            'updated_at' => now(),
                        ]);
                    $updated++;
                } else {
                    $this->db->table('products')->insert([
                        'id' => Str::uuid()->toString(),
                        'tenant_id' => $tenantId,
                        'onec_code' => $product['code'],
                        'name' => $product['name'],
                        'sku' => $product['sku'] ?? null,
                        'barcode' => $product['barcode'] ?? null,
                        'unit' => $product['unit'] ?? 'piece',
                        'price' => $product['price'] ?? 0,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $synced++;
                }
            }

            $this->logAction(
                action: 'onec_products_synced',
                entityType: 'OneCSync',
                entityId: (string) $tenantId,
                context: [
                    'products_synced' => $synced,
                    'products_updated' => $updated,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'success' => true,
                'products_synced' => $synced,
                'products_updated' => $updated,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to sync products from 1C', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync inventory to 1C
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $tenantId  Tenant ID
     * @param  int  $userId  User syncing
     * @return array Sync result
     */
    public function syncInventoryTo1C(int $warehouseId, int $tenantId, int $userId): array
    {
        $token = $this->getAuthToken();

        try {
            $inventoryItems = $this->db->table('inventory_items')
                ->where('warehouse_id', $warehouseId)
                ->where('tenant_id', $tenantId)
                ->get();

            $data = [
                'warehouse_id' => $warehouseId,
                'sync_date' => now()->toIso8601String(),
                'items' => $inventoryItems->map(function ($item) {
                    return [
                        'product_code' => $item->onec_code ?? $item->sku,
                        'quantity' => $item->quantity,
                        'reserved' => $item->reserved ?? 0,
                    ];
                })->toArray(),
            ];

            $response = Http::withToken($token)
                ->timeout(120)
                ->post(self::API_BASE_URL.'/inventory/sync', $data);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "1C API error: {$response->status()} - {$response->body()}"
                );
            }

            $result = $response->json();

            $this->logAction(
                action: 'onec_inventory_synced',
                entityType: 'OneCSync',
                entityId: (string) $warehouseId,
                context: [
                    'items_count' => $inventoryItems->count(),
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to sync inventory to 1C', [
                'warehouse_id' => $warehouseId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync suppliers from 1C
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $userId  User syncing
     * @return array Sync result
     */
    public function syncSuppliers(int $tenantId, int $userId): array
    {
        $token = $this->getAuthToken();

        try {
            $response = Http::withToken($token)
                ->timeout(60)
                ->get(self::API_BASE_URL.'/suppliers');

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "1C API error: {$response->status()} - {$response->body()}"
                );
            }

            $suppliers = $response->json('suppliers', []);

            $synced = 0;
            foreach ($suppliers as $supplier) {
                $this->db->table('suppliers')->updateOrInsert(
                    [
                        'onec_code' => $supplier['code'],
                        'tenant_id' => $tenantId,
                    ],
                    [
                        'name' => $supplier['name'],
                        'inn' => $supplier['inn'] ?? null,
                        'kpp' => $supplier['kpp'] ?? null,
                        'address' => $supplier['address'] ?? null,
                        'contact_person' => $supplier['contact_person'] ?? null,
                        'phone' => $supplier['phone'] ?? null,
                        'email' => $supplier['email'] ?? null,
                        'is_active' => true,
                        'updated_at' => now(),
                    ]
                );
                $synced++;
            }

            $this->logAction(
                action: 'onec_suppliers_synced',
                entityType: 'OneCSync',
                entityId: (string) $tenantId,
                context: [
                    'suppliers_synced' => $synced,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'success' => true,
                'suppliers_synced' => $synced,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to sync suppliers from 1C', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create financial document in 1C
     *
     * @param  array  $documentData  Document data
     * @param  int  $tenantId  Tenant ID
     * @param  int  $userId  User creating
     * @return array Creation result
     */
    public function createFinancialDocument(array $documentData, int $tenantId, int $userId): array
    {
        $token = $this->getAuthToken();

        try {
            $response = Http::withToken($token)
                ->timeout(60)
                ->post(self::API_BASE_URL.'/documents/financial', array_merge($documentData, [
                    'tenant_id' => $tenantId,
                    'created_by' => $userId,
                ]));

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "1C API error: {$response->status()} - {$response->body()}"
                );
            }

            $result = $response->json();

            $this->logAction(
                action: 'onec_financial_document_created',
                entityType: 'OneCDocument',
                entityId: $result['document_id'] ?? '',
                context: [
                    'document_type' => $documentData['type'] ?? 'unknown',
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create financial document in 1C', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync warehouse operations to 1C
     *
     * @param  string  $operationType  Operation type (receipt, shipment, adjustment)
     * @param  array  $operationData  Operation data
     * @param  int  $tenantId  Tenant ID
     * @param  int  $userId  User syncing
     * @return array Sync result
     */
    public function syncWarehouseOperation(
        string $operationType,
        array $operationData,
        int $tenantId,
        int $userId
    ): array {
        $token = $this->getAuthToken();

        try {
            $response = Http::withToken($token)
                ->timeout(60)
                ->post(self::API_BASE_URL.'/warehouse/operations', [
                    'operation_type' => $operationType,
                    'data' => $operationData,
                    'tenant_id' => $tenantId,
                    'synced_at' => now()->toIso8601String(),
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "1C API error: {$response->status()} - {$response->body()}"
                );
            }

            $result = $response->json();

            $this->logAction(
                action: 'onec_warehouse_operation_synced',
                entityType: 'OneCSync',
                entityId: $result['operation_id'] ?? '',
                context: [
                    'operation_type' => $operationType,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to sync warehouse operation to 1C', [
                'operation_type' => $operationType,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get authentication token from 1C
     *
     * @return string
     */
    private function getAuthToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, self::TOKEN_CACHE_TTL, function () {
            $response = Http::timeout(30)->post(self::API_BASE_URL.'/auth/login', [
                'username' => config('services.onec.username'),
                'password' => config('services.onec.password'),
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "Failed to get 1C auth token: {$response->status()}"
                );
            }

            return $response->json()['token'];
        });
    }
}
