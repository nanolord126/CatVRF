<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Jewelry\Pages;

use use App\Filament\Tenant\Resources\JewelryResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewJewelry extends ViewRecord
{
    protected static string $resource = JewelryResource::class;

    public function getTitle(): string
    {
        return 'View Jewelry';
    }
}