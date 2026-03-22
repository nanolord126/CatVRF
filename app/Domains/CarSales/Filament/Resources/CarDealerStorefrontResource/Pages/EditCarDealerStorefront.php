<?php declare(strict_types=1);

namespace App\Domains\CarSales\Filament\Resources\CarDealerStorefrontResource\Pages;

use App\Domains\CarSales\Filament\Resources\CarDealerStorefrontResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditCarDealerStorefront extends EditRecord
{
    protected static string $resource = CarDealerStorefrontResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
