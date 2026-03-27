<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\Food\Pages;

use App\Filament\Tenant\Resources\Food\RestaurantResource;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListRestaurants
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListRestaurants extends ListRecords
{
    protected static string $resource = RestaurantResource::class;
}
