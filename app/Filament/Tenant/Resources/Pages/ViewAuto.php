<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto\Pages;

use use App\Filament\Tenant\Resources\AutoResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewAuto extends ViewRecord
{
    protected static string $resource = AutoResource::class;

    public function getTitle(): string
    {
        return 'View Auto';
    }
}