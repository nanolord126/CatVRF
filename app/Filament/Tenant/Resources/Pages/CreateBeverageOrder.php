<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageOrder\Pages;

use use App\Filament\Tenant\Resources\BeverageOrderResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateBeverageOrder extends CreateRecord
{
    protected static string $resource = BeverageOrderResource::class;

    public function getTitle(): string
    {
        return 'Create BeverageOrder';
    }
}