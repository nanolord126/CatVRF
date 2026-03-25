declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MeatShopResource\Pages;

use App\Filament\Tenant\Resources\MeatShopResource;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListMeatShops
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListMeatShops extends ListRecords
{
    protected static string $resource = MeatShopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
