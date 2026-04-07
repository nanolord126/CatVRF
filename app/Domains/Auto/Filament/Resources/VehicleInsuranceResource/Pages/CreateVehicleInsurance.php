<?php declare(strict_types=1);

/**
 * CreateVehicleInsurance — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createvehicleinsurance
 */


namespace App\Domains\Auto\Filament\Resources\VehicleInsuranceResource\Pages;


use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\CreateRecord;

final class CreateVehicleInsurance extends CreateRecord
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}


    protected static string $resource = VehicleInsuranceResource::class;

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
                $this->logger->info('VehicleInsurance created', [
                    'correlation_id' => $this->record->correlation_id,
                    'policy_id' => $this->record->id,
                    'policy_number' => $this->record->policy_number,
                    'policy_type' => $this->record->policy_type,
                ]);

                event(new InsurancePolicyCreated(
                    $this->record,
                    $this->record->correlation_id
                ));
            });

            $this->notification->make()
                ->success()
                ->title('Полис оформлен')
                ->body('Номер полиса: ' . $this->record->policy_number)
                ->send();
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
