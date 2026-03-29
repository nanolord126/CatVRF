<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BakeryOrder\Pages;

use use App\Filament\Tenant\Resources\BakeryOrderResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateBakeryOrder extends CreateRecord
{
    protected static string $resource = BakeryOrderResource::class;

    public function getTitle(): string
    {
        return 'Create BakeryOrder';
    }
}