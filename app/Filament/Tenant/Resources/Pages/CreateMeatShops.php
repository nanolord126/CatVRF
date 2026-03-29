<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MeatShops\Pages;

use use App\Filament\Tenant\Resources\MeatShopsResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateMeatShops extends CreateRecord
{
    protected static string $resource = MeatShopsResource::class;

    public function getTitle(): string
    {
        return 'Create MeatShops';
    }
}