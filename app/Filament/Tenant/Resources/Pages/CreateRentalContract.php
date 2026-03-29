<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RentalContract\Pages;

use use App\Filament\Tenant\Resources\RentalContractResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateRentalContract extends CreateRecord
{
    protected static string $resource = RentalContractResource::class;

    public function getTitle(): string
    {
        return 'Create RentalContract';
    }
}