<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\BookingResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListBookings extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = BookingResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->after(function () {
                        Log::channel('audit')->info('Entertainment Booking creation started', [
                            'tenant_id' => filament()->getTenant()->id,
                            'user_id' => auth()->id(),
                        ]);
                    }),
            ];
        }
}
