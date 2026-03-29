<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Electronics\Pages;

use use App\Filament\Tenant\Resources\ElectronicsResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewElectronics extends ViewRecord
{
    protected static string $resource = ElectronicsResource::class;

    public function getTitle(): string
    {
        return 'View Electronics';
    }
}