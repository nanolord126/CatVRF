declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Cosmetics\CosmeticProductResource\Pages;

use App\Filament\Tenant\Resources\Cosmetics\CosmeticProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListCosmeticProducts
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListCosmeticProducts extends ListRecords
{
    protected static string $resource = CosmeticProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
