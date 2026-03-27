<?php

declare(strict_types=1);


namespace App\Domains\Auto\Filament\Resources\VehicleInsuranceResource\Pages;

use App\Domains\Auto\Filament\Resources\VehicleInsuranceResource;
use App\Domains\Auto\Events\InsurancePolicyCreated;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final /**
 * CreateVehicleInsurance
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateVehicleInsurance extends CreateRecord
{
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
        DB::transaction(function () {
            Log::channel('audit')->info('VehicleInsurance created', [
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
}
