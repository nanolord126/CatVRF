declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Food\Pages;

use App\Filament\Tenant\Resources\Food\RestaurantResource;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditRestaurant
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditRestaurant extends EditRecord
{
    protected static string $resource = RestaurantResource::class;
}
