<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PharmacyOrder\Pages;

use use App\Filament\Tenant\Resources\PharmacyOrderResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreatePharmacyOrder extends CreateRecord
{
    protected static string $resource = PharmacyOrderResource::class;

    public function getTitle(): string
    {
        return 'Create PharmacyOrder';
    }
}