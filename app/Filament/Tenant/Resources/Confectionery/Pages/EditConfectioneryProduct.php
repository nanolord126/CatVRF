<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Confectionery\Pages;

use App\Filament\Tenant\Resources\Confectionery\ConfectioneryProductResource;
use Filament\Resources\Pages\EditRecord;

final class EditConfectioneryProduct extends EditRecord
{
    protected static string $resource = ConfectioneryProductResource::class;
}
