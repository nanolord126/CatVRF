<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PharmacyOrder\Pages;

use use App\Filament\Tenant\Resources\PharmacyOrderResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewPharmacyOrder extends ViewRecord
{
    protected static string $resource = PharmacyOrderResource::class;

    public function getTitle(): string
    {
        return 'View PharmacyOrder';
    }
}