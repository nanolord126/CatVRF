declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\MasterResource\Pages;

use App\Filament\Tenant\Resources\Beauty\MasterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListMasters
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListMasters extends ListRecords
{
    protected static string $resource = MasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
