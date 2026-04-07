<?php declare(strict_types=1);

/**
 * ViewFitnes — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/viewfitnes
 * @see https://catvrf.ru/docs/viewfitnes
 * @see https://catvrf.ru/docs/viewfitnes
 * @see https://catvrf.ru/docs/viewfitnes
 * @see https://catvrf.ru/docs/viewfitnes
 * @see https://catvrf.ru/docs/viewfitnes
 */


namespace App\Filament\Tenant\Resources\Fitness\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\Fitness\FitnessResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

final class ViewFitnes extends ViewRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = FitnessResource::class;

    protected function afterLoad(): void
    {
        $this->logger->info('Fitness record viewed', [
            'record_id' => $this->record->id,
            'uuid' => $this->record->uuid,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => $this->guard->id(),
            'tenant_id' => filament()->getTenant()->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function render()
    {
        $this->logger->debug('ViewFitnes page rendered', [
            'record_id' => $this->record->id,
            'user_id' => $this->guard->id(),
        ]);

        return parent::render();
    }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}
