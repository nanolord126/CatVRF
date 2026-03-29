<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoPart\Pages;

use use App\Filament\Tenant\Resources\AutoPartResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewAutoPart extends ViewRecord
{
    protected static string $resource = AutoPartResource::class;

    public function getTitle(): string
    {
        return 'View AutoPart';
    }
}