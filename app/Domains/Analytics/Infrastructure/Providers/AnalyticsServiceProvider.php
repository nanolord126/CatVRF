<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Infrastructure\Providers;

use Illuminate\Config\Repository as ConfigRepository;

use App\Domains\Analytics\Domain\Interfaces\AnalyticsEventRepositoryInterface;
use App\Domains\Analytics\Domain\Interfaces\FraudScoringInterface;
use App\Domains\Analytics\Domain\Interfaces\VectorSearchInterface;
use App\Domains\Analytics\Infrastructure\Persistence\ClickHouse\ClickHouseAnalyticsEventRepository;
use App\Domains\Analytics\Infrastructure\Search\TypesenseVectorSearch;
use App\Domains\Analytics\Infrastructure\Services\MlFraudScoringService;
use Illuminate\Support\ServiceProvider;
use Typesense\Client;

/**
 * Class AnalyticsServiceProvider
 *
 * Part of the Analytics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Domains\Analytics\Infrastructure\Providers
 */
final class AnalyticsServiceProvider extends ServiceProvider
{
    /**
     * Handle register operation.
     *
     * @throws \DomainException
     */
    public function register(): void
    {
        $this->app->bind(AnalyticsEventRepositoryInterface::class, ClickHouseAnalyticsEventRepository::class);
        $this->app->bind(FraudScoringInterface::class, MlFraudScoringService::class);
        $this->app->bind(VectorSearchInterface::class, TypesenseVectorSearch::class);

        $this->app->singleton(Client::class, function ($app) {
            return new Client([
                'nodes' => [
                    [
                        'host' => $this->config->get('services.typesense.host'),
                        'port' => $this->config->get('services.typesense.port'),
                        'protocol' => $this->config->get('services.typesense.protocol'),
                    ],
                ],
                'api_key' => $this->config->get('services.typesense.key'),
                'connection_timeout_seconds' => 2,
            ]);
        });
    }
}
