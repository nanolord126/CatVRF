<?php

declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Integrations;

use Illuminate\Http\Client\Factory;
use Psr\Log\LoggerInterface;

final class PartnerStoreAPIIntegration
{
    private const PROVIDER_ENDPOINTS = [
        'magnit' => 'https://api.magnit.com/v1',
        'pyaterochka' => 'https://api.pyaterochka.com/v1',
        'vkusvill' => 'https://api.vkusvill.com/v1',
    ];

    public function __construct(
        private readonly Factory $http,
        private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Синхронизировать товары и остатки из внешнего магазина.
     */
    public function syncInventory(
        GroceryStore $store,
        string $correlationId,
    ): array {
        if (!$store->api_provider || !$store->api_token) {
            throw new \RuntimeException('Store API credentials not configured');
        }

        $endpoint = self::PROVIDER_ENDPOINTS[$store->api_provider] ?? null;
        if (!$endpoint) {
            throw new \RuntimeException("Unsupported API provider: {$store->api_provider}");
        }

        try {
            $response = $this->http->withToken($store->api_token)
                ->timeout(30)
                ->get("{$endpoint}/catalog/products", [
                    'store_id' => $store->id,
                    'limit' => 1000,
                ]);

            if (!$response->successful()) {
                throw new \RuntimeException("API call failed: {$response->status()}");
            }

            $products = $response->json('data', []);

            $this->logger->info('PartnerStoreAPI: inventory synced', [
                'store_id' => $store->id,
                'products_count' => count($products),
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => true,
                'products_count' => count($products),
                'products' => $products,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('PartnerStoreAPI: sync failed', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}