<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Jewelry\JewelryItemResource\Pages;

use App\Filament\Tenant\Resources\Jewelry\JewelryItemResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateJewelryItem extends CreateRecord
{
    protected static string $resource = JewelryItemResource::class;
}
