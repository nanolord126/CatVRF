<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * 1C Integration Service for Russian ERP
 *
 * Implements integration with 1C:Enterprise (1С:Предприятие) ERP system:
 * - Product catalog synchronization
 * - Inventory synchronization
 * - Order synchronization
 * - Financial document exchange
 * - EDI (Electronic Data Interchange) support
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class OneCIntegrationService
{
    private const API_VERSION = 'v2';
    private const ENDPOINT_PRODUCTS = '/catalog/products';
    private const ENDPOINT_INVENTORY = '/inventory';
    private const ENDPOINT_ORDERS = '/documents/orders';
    private const ENDPOINT_INVOICES = '/documents/invoices';

    public function __construct(
        private readonly string $apiUrl,
        private readonly string $username,
        private readonly string $password,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Synchronize product catalog from 1C
     *
     * @param  int  $tenantId  Tenant ID
     * @param  string|null  $lastSyncDate  Last synchronization date
     * @return array Synchronization result
     */
    public function syncProducts(int $tenantId, ?string $lastSyncDate = null): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(120)
                ->get(
                    "{$this->apiUrl}/" . self::API_VERSION . self::ENDPOINT_PRODUCTS,
                    [
                        'tenant_id' => $tenantId,
                        'last_sync' => $lastSyncDate,
                        'limit' => 1000,
                    ]
                );

            if (! $response->successful()) {
                $this->logger->error('1C product sync failed', [
                    'tenant_id' => $tenantId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new \RuntimeException('1C product sync failed');
            }

            $products = $response->json();

            $syncResult = [
                'total_products' => count($products),
                'synced_at' => now()->toIso8601String(),
                'products' => [],
            ];

            foreach ($products as $product) {
                $syncResult['products'][] = [
                    'sku' => $product['sku'],
                    'name' => $product['name'],
                    'barcode' => $product['barcode'] ?? null,
                    'unit' => $product['unit'],
                    'price' => $product['price'],
                ];
            }

            $this->logger->info('1C products synchronized', [
                'tenant_id' => $tenantId,
                'total_products' => $syncResult['total_products'],
            ]);

            return $syncResult;
        } catch (\Exception $e) {
            $this->logger->error('1C product sync error', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Synchronize inventory levels from 1C
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $warehouseId  Warehouse ID
     * @return array Inventory data
     */
    public function syncInventory(int $tenantId, int $warehouseId): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(120)
                ->get(
                    "{$this->apiUrl}/" . self::API_VERSION . self::ENDPOINT_INVENTORY,
                    [
                        'tenant_id' => $tenantId,
                        'warehouse_id' => $warehouseId,
                    ]
                );

            if (! $response->successful()) {
                $this->logger->error('1C inventory sync failed', [
                    'tenant_id' => $tenantId,
                    'warehouse_id' => $warehouseId,
                    'status' => $response->status(),
                ]);

                throw new \RuntimeException('1C inventory sync failed');
            }

            $inventory = $response->json();

            $this->logger->info('1C inventory synchronized', [
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'total_items' => count($inventory),
            ]);

            return $inventory;
        } catch (\Exception $e) {
            $this->logger->error('1C inventory sync error', [
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send order to 1C
     *
     * @param  array<string, mixed>  $orderData  Order data
     * @param  int  $tenantId  Tenant ID
     * @return string Order ID in 1C
     */
    public function sendOrder(array $orderData, int $tenantId): string
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(60)
                ->post(
                    "{$this->apiUrl}/" . self::API_VERSION . self::ENDPOINT_ORDERS,
                    array_merge($orderData, [
                        'tenant_id' => $tenantId,
                        'document_date' => now()->format('Y-m-d'),
                    ])
                );

            if (! $response->successful()) {
                $this->logger->error('1C order send failed', [
                    'tenant_id' => $tenantId,
                    'order_number' => $orderData['order_number'] ?? null,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new \RuntimeException('1C order send failed');
            }

            $result = $response->json();

            $this->logger->info('1C order sent', [
                'tenant_id' => $tenantId,
                'order_id' => $result['order_id'],
                'document_number' => $result['document_number'],
            ]);

            return $result['order_id'];
        } catch (\Exception $e) {
            $this->logger->error('1C order send error', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get invoice from 1C
     *
     * @param  string  $invoiceNumber  Invoice number
     * @param  int  $tenantId  Tenant ID
     * @return array Invoice data
     */
    public function getInvoice(string $invoiceNumber, int $tenantId): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(30)
                ->get(
                    "{$this->apiUrl}/" . self::API_VERSION . self::ENDPOINT_INVOICES . "/{$invoiceNumber}",
                    [
                        'tenant_id' => $tenantId,
                    ]
                );

            if (! $response->successful()) {
                $this->logger->error('1C invoice retrieval failed', [
                    'tenant_id' => $tenantId,
                    'invoice_number' => $invoiceNumber,
                    'status' => $response->status(),
                ]);

                throw new \RuntimeException('1C invoice retrieval failed');
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logger->error('1C invoice retrieval error', [
                'tenant_id' => $tenantId,
                'invoice_number' => $invoiceNumber,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send stock movement to 1C
     *
     * @param  array<string, mixed>  $movementData  Movement data
     * @param  int  $tenantId  Tenant ID
     * @return string Document ID in 1C
     */
    public function sendStockMovement(array $movementData, int $tenantId): string
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(60)
                ->post(
                    "{$this->apiUrl}/" . self::API_VERSION . '/documents/stock_movements',
                    array_merge($movementData, [
                        'tenant_id' => $tenantId,
                        'document_date' => now()->format('Y-m-d'),
                    ])
                );

            if (! $response->successful()) {
                $this->logger->error('1C stock movement send failed', [
                    'tenant_id' => $tenantId,
                    'movement_type' => $movementData['type'],
                    'status' => $response->status(),
                ]);

                throw new \RuntimeException('1C stock movement send failed');
            }

            $result = $response->json();

            $this->logger->info('1C stock movement sent', [
                'tenant_id' => $tenantId,
                'document_id' => $result['document_id'],
            ]);

            return $result['document_id'];
        } catch (\Exception $e) {
            $this->logger->error('1C stock movement send error', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get product price from 1C
     *
     * @param  string  $sku  Product SKU
     * @param  int  $tenantId  Tenant ID
     * @return array Price data
     */
    public function getProductPrice(string $sku, int $tenantId): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(10)
                ->get(
                    "{$this->apiUrl}/" . self::API_VERSION . self::ENDPOINT_PRODUCTS . "/{$sku}/price",
                    [
                        'tenant_id' => $tenantId,
                    ]
                );

            if (! $response->successful()) {
                $this->logger->warning('1C product price retrieval failed', [
                    'tenant_id' => $tenantId,
                    'sku' => $sku,
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logger->warning('1C product price retrieval error', [
                'tenant_id' => $tenantId,
                'sku' => $sku,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get counterparties from 1C
     *
     * @param  int  $tenantId  Tenant ID
     * @param  string|null  $type  Counterparty type (supplier, customer)
     * @return array Counterparties
     */
    public function getCounterparties(int $tenantId, ?string $type = null): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(30)
                ->get(
                    "{$this->apiUrl}/" . self::API_VERSION . '/catalog/counterparties',
                    [
                        'tenant_id' => $tenantId,
                        'type' => $type,
                    ]
                );

            if (! $response->successful()) {
                $this->logger->warning('1C counterparties retrieval failed', [
                    'tenant_id' => $tenantId,
                    'type' => $type,
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logger->warning('1C counterparties retrieval error', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Test 1C connection
     *
     * @return bool Connection status
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(10)
                ->get("{$this->apiUrl}/health");

            $isConnected = $response->successful();

            $this->logger->info('1C connection test', [
                'status' => $isConnected ? 'success' : 'failed',
                'response_time' => $response->handlerStats() ? $response->handlerStats()['total_time'] : null,
            ]);

            return $isConnected;
        } catch (\Exception $e) {
            $this->logger->error('1C connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get exchange status with 1C
     *
     * @param  int  $tenantId  Tenant ID
     * @return array Exchange status
     */
    public function getExchangeStatus(int $tenantId): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(10)
                ->get(
                    "{$this->apiUrl}/" . self::API_VERSION . '/exchange/status',
                    [
                        'tenant_id' => $tenantId,
                    ]
                );

            if (! $response->successful()) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to get exchange status',
                ];
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logger->error('1C exchange status error', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}
