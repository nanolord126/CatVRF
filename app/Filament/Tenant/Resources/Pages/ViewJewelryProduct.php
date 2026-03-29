<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\JewelryProduct\Pages;

use use App\Filament\Tenant\Resources\JewelryProductResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewJewelryProduct extends ViewRecord
{
    protected static string $resource = JewelryProductResource::class;

    public function getTitle(): string
    {
        return 'View JewelryProduct';
    }
}