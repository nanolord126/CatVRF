declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ShortTermRentals\ApartmentResource\Pages;

use App\Filament\Tenant\Resources\ShortTermRentals\ApartmentResource;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListApartments
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListApartments extends ListRecords
{
    protected static string $resource = ApartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
