<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoPartOrder\Pages;

use use App\Filament\Tenant\Resources\AutoPartOrderResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewAutoPartOrder extends ViewRecord
{
    protected static string $resource = AutoPartOrderResource::class;

    public function getTitle(): string
    {
        return 'View AutoPartOrder';
    }
}