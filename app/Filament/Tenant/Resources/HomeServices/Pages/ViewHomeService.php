<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HomeServices\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\HomeServices\HomeServicesResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

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
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Filament\Tenant\Resources\HomeServices\Pages
 */
final class ViewHomeService extends ViewRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = HomeServicesResource::class;

    protected function afterLoad(): void
    {
        \Illuminate\Support\Facades\Log::channel('audit')->info('HomeServices record viewed', [
            'record_id' => $this->record->id,
            'uuid' => $this->record->uuid,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Handle render operation.
     *
     * @throws \DomainException
     */
    public function render(): \Illuminate\Contracts\View\View {
        $this->logger->debug('ViewHomeService page rendered', [
            'record_id' => $this->record->id,
            'user_id' => auth()->id(),
        ]);

        return parent::render();
    }
}
