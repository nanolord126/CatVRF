<?php

declare(strict_types=1);

/**
 * CreateVehicleInsurance — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/createvehicleinsurance
 */

namespace App\Domains\Auto\Filament\Resources\VehicleInsuranceResource\Pages;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\DatabaseManager;

final class CreateVehicleInsurance extends CreateRecord
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';


    protected static string $resource = VehicleInsuranceResource::class;

    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger) {}

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = Str::uuid()->toString();
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['uuid'] = Str::uuid()->toString();
        $data['correlation_id'] = $correlationId;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->db->transaction(function () {
            $this->logger->$this->logger->info('VehicleInsurance created', [
                'correlation_id' => $this->record->correlation_id,
                'policy_id' => $this->record->id,
                'policy_number' => $this->record->policy_number,
                'policy_type' => $this->record->policy_type,
            ]);

            $this->eventDispatcher->dispatch(new InsurancePolicyCreated(
                $this->record,
                $this->record->correlation_id
            ));
        });

        $this->notification->make()
            ->success()
            ->title('Полис оформлен')
            ->body('Номер полиса: '.$this->record->policy_number)
            ->send();
    }
}
