<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StationeryProduct\Pages;

use use App\Filament\Tenant\Resources\StationeryProductResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateStationeryProduct extends CreateRecord
{
    protected static string $resource = StationeryProductResource::class;

    public function getTitle(): string
    {
        return 'Create StationeryProduct';
    }
}