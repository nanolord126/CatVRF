<?php

declare(strict_types=1);


namespace App\Domains\Auto\Filament\Resources\VehicleInsuranceResource\Pages;

use App\Domains\Auto\Filament\Resources\VehicleInsuranceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

final /**
 * EditVehicleInsurance
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditVehicleInsurance extends EditRecord
{
    protected static string $resource = VehicleInsuranceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    Log::channel('audit')->info('VehicleInsurance deleted', [
                        'correlation_id' => $this->record->correlation_id,
                        'policy_id' => $this->record->id,
                    ]);
                }),
        ];
    }

    protected function afterSave(): void
    {
        Log::channel('audit')->info('VehicleInsurance updated', [
            'correlation_id' => $this->record->correlation_id,
            'policy_id' => $this->record->id,
            'status' => $this->record->status,
        ]);
    }
}
