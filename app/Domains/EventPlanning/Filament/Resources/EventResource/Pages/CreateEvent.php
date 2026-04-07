<?php

declare(strict_types=1);

namespace App\Domains\EventPlanning\Filament\Resources\EventResource\Pages;

use App\Domains\EventPlanning\Filament\Resources\EventResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;
}
