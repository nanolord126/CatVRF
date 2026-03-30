<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VehicleResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditVehicle extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = VehicleResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
            ];
        }

        protected function afterSave(): void
        {
            activity()
                ->performedBy(auth()->user())
                ->on($this->record)
                ->withProperty('correlation_id', $this->record->correlation_id)
                ->log('Vehicle information updated');
        }
}
