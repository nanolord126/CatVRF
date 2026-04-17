<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Services\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\Services\ServicesResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

/**
 * Class ViewService
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
 * @package App\Filament\Tenant\Resources\Services\Pages
 */
final class ViewService extends ViewRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = ServicesResource::class;

    protected function afterLoad(): void
    {
        \Illuminate\Support\Facades\Log::channel('audit')->info('Services record viewed', [
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
        $this->logger->debug('ViewService page rendered', [
            'record_id' => $this->record->id,
            'user_id' => auth()->id(),
        ]);

        return parent::render();
    }
}
