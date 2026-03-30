<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\VehicleInsuranceResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditVehicleInsurance extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
