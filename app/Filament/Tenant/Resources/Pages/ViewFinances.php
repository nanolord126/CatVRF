<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Finances\Pages;

use use App\Filament\Tenant\Resources\FinancesResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewFinances extends ViewRecord
{
    protected static string $resource = FinancesResource::class;

    public function getTitle(): string
    {
        return 'View Finances';
    }
}