<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Filament\Resources\BookingResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListBookings extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = BookingResource::class;

        protected function getHeaderActions(): array
        {
            return [Actions\CreateAction::make()];
        }
}
