<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\VehicleInspectionResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditVehicleInspection extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
