<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalConsumable\Pages;

use use App\Filament\Tenant\Resources\DentalConsumableResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateDentalConsumable extends CreateRecord
{
    protected static string $resource = DentalConsumableResource::class;

    public function getTitle(): string
    {
        return 'Create DentalConsumable';
    }
}