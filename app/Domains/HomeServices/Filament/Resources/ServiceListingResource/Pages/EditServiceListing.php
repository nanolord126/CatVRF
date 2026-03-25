declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ServiceListingResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ServiceListingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditServiceListing
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditServiceListing extends EditRecord
{
    protected static string $resource = ServiceListingResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
