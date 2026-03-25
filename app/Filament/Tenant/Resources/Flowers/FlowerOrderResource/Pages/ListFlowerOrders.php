declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\FlowerOrderResource\Pages;

use App\Filament\Tenant\Resources\Flowers\FlowerOrderResource;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListFlowerOrders
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListFlowerOrders extends ListRecords
{
    protected static string $resource = FlowerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
