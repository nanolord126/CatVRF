declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\OfficeCateringResource\Pages;

use App\Filament\Tenant\Resources\OfficeCateringResource;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListOfficeCaterings
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListOfficeCaterings extends ListRecords
{
    protected static string $resource = OfficeCateringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
