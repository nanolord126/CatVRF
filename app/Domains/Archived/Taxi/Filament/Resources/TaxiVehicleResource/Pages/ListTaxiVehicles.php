<?php declare(strict_types=1);

namespace App\Domains\Archived\Taxi\Filament\Resources\TaxiVehicleResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListTaxiVehicles extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = TaxiVehicleResource::class;


        protected function getHeaderActions(): array


        {


            return [Actions\CreateAction::make()];


        }
}
