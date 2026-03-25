declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\BouquetResource\Pages;

use App\Filament\Tenant\Resources\Flowers\BouquetResource;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditBouquet
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditBouquet extends EditRecord
{
    protected static string $resource = BouquetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
