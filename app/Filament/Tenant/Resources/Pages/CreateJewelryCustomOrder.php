<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\JewelryCustomOrder\Pages;

use use App\Filament\Tenant\Resources\JewelryCustomOrderResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateJewelryCustomOrder extends CreateRecord
{
    protected static string $resource = JewelryCustomOrderResource::class;

    public function getTitle(): string
    {
        return 'Create JewelryCustomOrder';
    }
}