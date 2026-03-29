<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ElectronicOrder\Pages;

use use App\Filament\Tenant\Resources\ElectronicOrderResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewElectronicOrder extends ViewRecord
{
    protected static string $resource = ElectronicOrderResource::class;

    public function getTitle(): string
    {
        return 'View ElectronicOrder';
    }
}