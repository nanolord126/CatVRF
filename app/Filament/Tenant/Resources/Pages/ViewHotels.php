<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotels\Pages;

use use App\Filament\Tenant\Resources\HotelsResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewHotels extends ViewRecord
{
    protected static string $resource = HotelsResource::class;

    public function getTitle(): string
    {
        return 'View Hotels';
    }
}