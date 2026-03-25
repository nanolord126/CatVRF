declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\SportingGoods\Pages;

use App\Filament\Tenant\Resources\SportingGoods\SportProductResource;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListSportProducts
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListSportProducts extends ListRecords
{
    protected static string $resource = SportProductResource::class;
}
