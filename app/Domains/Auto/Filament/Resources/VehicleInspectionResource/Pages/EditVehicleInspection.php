<?php

declare(strict_types=1);


namespace App\Domains\Auto\Filament\Resources\VehicleInspectionResource\Pages;

use App\Domains\Auto\Filament\Resources\VehicleInspectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

final /**
 * EditVehicleInspection
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditVehicleInspection extends EditRecord
{
    protected static string $resource = VehicleInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    Log::channel('audit')->info('VehicleInspection deleted', [
                        'correlation_id' => $this->record->correlation_id,
                        'inspection_id' => $this->record->id,
                    ]);
                }),
        ];
    }

    protected function afterSave(): void
    {
        Log::channel('audit')->info('VehicleInspection updated', [
            'correlation_id' => $this->record->correlation_id,
            'inspection_id' => $this->record->id,
            'status' => $this->record->status,
        ]);
    }
}
