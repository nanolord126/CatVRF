<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CorporateOrder\Pages;

use use App\Filament\Tenant\Resources\CorporateOrderResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateCorporateOrder extends CreateRecord
{
    protected static string $resource = CorporateOrderResource::class;

    public function getTitle(): string
    {
        return 'Create CorporateOrder';
    }
}