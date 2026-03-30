<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ServiceListingResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListServiceListings extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = ServiceListingResource::class;

        protected function getHeaderActions(): array
        {
            return [\Filament\Actions\CreateAction::make()];
        }
}
