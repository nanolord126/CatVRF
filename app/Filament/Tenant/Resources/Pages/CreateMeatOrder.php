<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MeatOrder\Pages;

use use App\Filament\Tenant\Resources\MeatOrderResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateMeatOrder extends CreateRecord
{
    protected static string $resource = MeatOrderResource::class;

    public function getTitle(): string
    {
        return 'Create MeatOrder';
    }
}