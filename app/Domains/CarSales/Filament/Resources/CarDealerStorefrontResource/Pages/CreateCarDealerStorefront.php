<?php declare(strict_types=1);

namespace App\Domains\CarSales\Filament\Resources\CarDealerStorefrontResource\Pages;

use App\Domains\CarSales\Filament\Resources\CarDealerStorefrontResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCarDealerStorefront extends CreateRecord
{
    protected static string $resource = CarDealerStorefrontResource::class;
}
