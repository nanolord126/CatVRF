<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ToyOrder\Pages;

use use App\Filament\Tenant\Resources\ToyOrderResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateToyOrder extends CreateRecord
{
    protected static string $resource = ToyOrderResource::class;

    public function getTitle(): string
    {
        return 'Create ToyOrder';
    }
}