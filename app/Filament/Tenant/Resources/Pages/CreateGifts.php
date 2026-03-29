<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Gifts\Pages;

use use App\Filament\Tenant\Resources\GiftsResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateGifts extends CreateRecord
{
    protected static string $resource = GiftsResource::class;

    public function getTitle(): string
    {
        return 'Create Gifts';
    }
}