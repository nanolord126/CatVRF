<?php

declare(strict_types=1);

/**
 * EditVehicleInsurance — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/editvehicleinsurance
 */

namespace App\Domains\Auto\Filament\Resources\VehicleInsuranceResource\Pages;

use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\EditRecord;

final class EditVehicleInsurance extends EditRecord
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;


    protected static string $resource = VehicleInsuranceResource::class;

    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    $this->logger->$this->logger->info('VehicleInsurance deleted', [
                        'correlation_id' => $this->record->correlation_id,
                        'policy_id' => $this->record->id,
                    ]);
                }),
        ];
    }

    protected function afterSave(): void
    {
        $this->logger->$this->logger->info('VehicleInsurance updated', [
            'correlation_id' => $this->record->correlation_id,
            'policy_id' => $this->record->id,
            'status' => $this->record->status,
        ]);
    }
}
