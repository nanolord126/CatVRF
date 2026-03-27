<?php

declare(strict_types=1);


namespace App\Domains\Auto\CarSales\Filament\Resources\CarDealerStorefrontResource\Pages;

use App\Domains\Auto\CarSales\Filament\Resources\CarDealerStorefrontResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditCarDealerStorefront
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditCarDealerStorefront extends EditRecord
{
    protected static string $resource = CarDealerStorefrontResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
