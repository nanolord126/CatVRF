declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto\AutoPartResource\Pages;

use App\Filament\Tenant\Resources\Auto\AutoPartResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final /**
 * ViewAutoPart
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ViewAutoPart extends ViewRecord
{
    protected static string $resource = AutoPartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
