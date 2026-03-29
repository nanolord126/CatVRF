<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RentalContract\Pages;

use use App\Filament\Tenant\Resources\RentalContractResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewRentalContract extends ViewRecord
{
    protected static string $resource = RentalContractResource::class;

    public function getTitle(): string
    {
        return 'View RentalContract';
    }
}