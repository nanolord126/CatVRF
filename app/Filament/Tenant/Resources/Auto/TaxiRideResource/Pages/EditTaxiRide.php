declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto\TaxiRideResource\Pages;

use App\Filament\Tenant\Resources\Auto\TaxiRideResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditTaxiRide
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditTaxiRide extends EditRecord
{
    protected static string $resource = TaxiRideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
