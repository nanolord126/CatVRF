<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Finances\Pages;

use use App\Filament\Tenant\Resources\FinancesResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateFinances extends CreateRecord
{
    protected static string $resource = FinancesResource::class;

    public function getTitle(): string
    {
        return 'Create Finances';
    }
}