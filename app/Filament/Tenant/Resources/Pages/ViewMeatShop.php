<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MeatShop\Pages;

use use App\Filament\Tenant\Resources\MeatShopResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewMeatShop extends ViewRecord
{
    protected static string $resource = MeatShopResource::class;

    public function getTitle(): string
    {
        return 'View MeatShop';
    }
}