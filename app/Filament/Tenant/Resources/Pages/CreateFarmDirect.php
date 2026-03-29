<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FarmDirect\Pages;

use use App\Filament\Tenant\Resources\FarmDirectResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateFarmDirect extends CreateRecord
{
    protected static string $resource = FarmDirectResource::class;

    public function getTitle(): string
    {
        return 'Create FarmDirect';
    }
}