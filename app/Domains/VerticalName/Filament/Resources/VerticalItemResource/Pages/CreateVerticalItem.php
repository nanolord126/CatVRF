<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Filament\Resources\VerticalItemResource\Pages;

use App\Domains\VerticalName\Filament\Resources\VerticalItemResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateVerticalItem extends CreateRecord
{
    protected static string $resource = VerticalItemResource::class;
}
