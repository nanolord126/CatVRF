<?php

declare(strict_types=1);

namespace App\Filament\Resources\BusinessGroupResource\Pages;

use App\Filament\Resources\BusinessGroupResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateBusinessGroup extends CreateRecord
{
    protected static string $resource = BusinessGroupResource::class;
}
