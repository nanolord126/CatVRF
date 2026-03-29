<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoParts\Pages;

use use App\Filament\Tenant\Resources\AutoPartsResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewAutoParts extends ViewRecord
{
    protected static string $resource = AutoPartsResource::class;

    public function getTitle(): string
    {
        return 'View AutoParts';
    }
}