<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\Logistics\CourierResource\Pages;

use App\Filament\Tenant\Resources\Logistics\CourierResource;
use Filament\Resources\Pages\ViewRecord;

final /**
 * ViewCourier
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ViewCourier extends ViewRecord
{
    protected static string $resource = CourierResource::class;
}
