<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BakeryOrder\Pages;

use use App\Filament\Tenant\Resources\BakeryOrderResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewBakeryOrder extends ViewRecord
{
    protected static string $resource = BakeryOrderResource::class;

    public function getTitle(): string
    {
        return 'View BakeryOrder';
    }
}