<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VIPBookingResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListVIPBookings extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = VIPBookingResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->label('Создать бронирование')
                    ->icon('heroicon-o-plus-circle'),
            ];
        }
}
