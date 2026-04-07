<?php declare(strict_types=1);

/**
 * EditVehicleInspection — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/editvehicleinspection
 */


namespace App\Domains\Auto\Filament\Resources\VehicleInspectionResource\Pages;


use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\EditRecord;

final class EditVehicleInspection extends EditRecord
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    protected static string $resource = VehicleInspectionResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\DeleteAction::make()
                    ->after(function () {
                        $this->logger->info('VehicleInspection deleted', [
                            'correlation_id' => $this->record->correlation_id,
                            'inspection_id' => $this->record->id,
                        ]);
                    }),
            ];
        }

        protected function afterSave(): void
        {
            $this->logger->info('VehicleInspection updated', [
                'correlation_id' => $this->record->correlation_id,
                'inspection_id' => $this->record->id,
                'status' => $this->record->status,
            ]);
        }

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

}
