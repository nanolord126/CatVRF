<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Tickets\EventResource\Pages;

use App\Filament\Tenant\Resources\Tickets\EventResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;
}
