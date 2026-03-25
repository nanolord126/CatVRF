declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Grocery\Filament\Resources\GroceryStoreResource\Pages;

use App\Domains\Grocery\Filament\Resources\GroceryStoreResource;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditGroceryStore
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditGroceryStore extends EditRecord
{
    protected static string $resource = GroceryStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\DeleteAction::make()];
    }
}
