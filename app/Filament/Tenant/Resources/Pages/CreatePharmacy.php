<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pharmacy\Pages;

use use App\Filament\Tenant\Resources\PharmacyResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreatePharmacy extends CreateRecord
{
    protected static string $resource = PharmacyResource::class;

    public function getTitle(): string
    {
        return 'Create Pharmacy';
    }
}