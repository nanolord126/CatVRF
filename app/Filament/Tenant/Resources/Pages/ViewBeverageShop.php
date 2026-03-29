<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageShop\Pages;

use use App\Filament\Tenant\Resources\BeverageShopResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewBeverageShop extends ViewRecord
{
    protected static string $resource = BeverageShopResource::class;

    public function getTitle(): string
    {
        return 'View BeverageShop';
    }
}