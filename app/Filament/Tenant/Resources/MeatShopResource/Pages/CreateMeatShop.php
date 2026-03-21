<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MeatShopResource\Pages;

use App\Filament\Tenant\Resources\MeatShopResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateMeatShop extends CreateRecord
{
    protected static string $resource = MeatShopResource::class;
}
