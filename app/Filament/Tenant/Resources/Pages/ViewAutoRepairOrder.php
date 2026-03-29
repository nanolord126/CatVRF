<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoRepairOrder\Pages;

use use App\Filament\Tenant\Resources\AutoRepairOrderResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewAutoRepairOrder extends ViewRecord
{
    protected static string $resource = AutoRepairOrderResource::class;

    public function getTitle(): string
    {
        return 'View AutoRepairOrder';
    }
}