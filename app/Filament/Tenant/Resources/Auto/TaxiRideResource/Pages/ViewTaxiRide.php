<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto\TaxiRideResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ViewTaxiRide extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = TaxiRideResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\EditAction::make(),
            ];
        }
}
