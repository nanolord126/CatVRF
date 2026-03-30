<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\VenueResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListVenues extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = VenueResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->after(function () {
                        Log::channel('audit')->info('Venue creation started', [
                            'tenant_id' => filament()->getTenant()->id,
                            'user_id' => auth()->id(),
                        ]);
                    }),
            ];
        }
}
