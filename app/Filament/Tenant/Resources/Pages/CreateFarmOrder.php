<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FarmOrder\Pages;

use use App\Filament\Tenant\Resources\FarmOrderResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateFarmOrder extends CreateRecord
{
    protected static string $resource = FarmOrderResource::class;

    public function getTitle(): string
    {
        return 'Create FarmOrder';
    }
}