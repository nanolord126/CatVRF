<?php

declare(strict_types=1);

namespace App\Filament\Resources\SecurityEventResource\Pages;

use App\Filament\Resources\SecurityEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSecurityEvent extends CreateRecord
{
    protected static string $resource = SecurityEventResource::class;
}
