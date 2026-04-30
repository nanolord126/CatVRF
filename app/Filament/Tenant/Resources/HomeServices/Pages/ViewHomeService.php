<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HomeServices\Pages;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use App\Filament\Tenant\Resources\HomeServices\HomeServicesResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Log\LogManager;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\View\View;

/**
 * Class ViewHomeService
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see FraudControlService
 * @see AuditService
 */
final class ViewHomeService extends ViewRecord
{
    protected static string $resource = HomeServicesResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    /**
     * Handle render operation.
     *
     * @throws \DomainException
     */
    public function render(): View
    {
        $this->logger->debug('ViewHomeService page rendered', [
            'record_id' => $this->record->id,
            'user_id' => auth()->id(),
        ]);

        return parent::render();
    }

    protected function afterLoad(): void
    {
        $this->log->channel('audit')->$this->logger->info('HomeServices record viewed', [
            'record_id' => $this->record->id,
            'uuid' => $this->record->uuid,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]);
    }
}
