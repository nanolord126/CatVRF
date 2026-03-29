<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageShop\Pages;

use use App\Filament\Tenant\Resources\BeverageShopResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateBeverageShop extends CreateRecord
{
    protected static string $resource = BeverageShopResource::class;

    public function getTitle(): string
    {
        return 'Create BeverageShop';
    }
}