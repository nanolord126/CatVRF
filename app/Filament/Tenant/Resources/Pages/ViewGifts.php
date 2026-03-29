<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Gifts\Pages;

use use App\Filament\Tenant\Resources\GiftsResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewGifts extends ViewRecord
{
    protected static string $resource = GiftsResource::class;

    public function getTitle(): string
    {
        return 'View Gifts';
    }
}