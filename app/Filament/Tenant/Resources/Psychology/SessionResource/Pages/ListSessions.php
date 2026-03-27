<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Psychology\SessionResource\Pages;

use App\Filament\Tenant\Resources\Psychology\SessionResource;
use Filament\Resources\Pages\ListRecords;

final class ListSessions extends ListRecords
{
    protected static string $resource = SessionResource::class;
}
