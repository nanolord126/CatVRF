<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Medical\AppointmentResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditAppointment extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = AppointmentResource::class;

        protected function getHeaderActions(): array
        {
            return [
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ];
        }
}
