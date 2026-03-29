<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Jewelry\Pages;

use use App\Filament\Tenant\Resources\JewelryResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateJewelry extends CreateRecord
{
    protected static string $resource = JewelryResource::class;

    public function getTitle(): string
    {
        return 'Create Jewelry';
    }
}