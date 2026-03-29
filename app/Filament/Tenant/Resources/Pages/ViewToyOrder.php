<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ToyOrder\Pages;

use use App\Filament\Tenant\Resources\ToyOrderResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewToyOrder extends ViewRecord
{
    protected static string $resource = ToyOrderResource::class;

    public function getTitle(): string
    {
        return 'View ToyOrder';
    }
}