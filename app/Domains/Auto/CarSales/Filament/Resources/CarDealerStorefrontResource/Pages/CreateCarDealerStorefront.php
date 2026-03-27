<?php

declare(strict_types=1);


namespace App\Domains\Auto\CarSales\Filament\Resources\CarDealerStorefrontResource\Pages;

use App\Domains\Auto\CarSales\Filament\Resources\CarDealerStorefrontResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateCarDealerStorefront
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateCarDealerStorefront extends CreateRecord
{
    protected static string $resource = CarDealerStorefrontResource::class;
}
