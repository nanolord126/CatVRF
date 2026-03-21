<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Confectionery\Pages;

use App\Filament\Tenant\Resources\Confectionery\ConfectioneryProductResource;
use Filament\Resources\Pages\ListRecords;

final class ListConfectioneryProducts extends ListRecords
{
    protected static string $resource = ConfectioneryProductResource::class;
}
