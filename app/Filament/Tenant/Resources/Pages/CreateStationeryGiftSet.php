<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StationeryGiftSet\Pages;

use use App\Filament\Tenant\Resources\StationeryGiftSetResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateStationeryGiftSet extends CreateRecord
{
    protected static string $resource = StationeryGiftSetResource::class;

    public function getTitle(): string
    {
        return 'Create StationeryGiftSet';
    }
}