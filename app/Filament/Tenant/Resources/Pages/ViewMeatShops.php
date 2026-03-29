<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MeatShops\Pages;

use use App\Filament\Tenant\Resources\MeatShopsResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewMeatShops extends ViewRecord
{
    protected static string $resource = MeatShopsResource::class;

    public function getTitle(): string
    {
        return 'View MeatShops';
    }
}