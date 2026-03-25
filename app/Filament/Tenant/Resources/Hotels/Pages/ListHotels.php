declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotels\Pages;

use App\Filament\Tenant\Resources\Hotels\HotelResource;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListHotels
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListHotels extends ListRecords
{
    protected static string $resource = HotelResource::class;
}
