<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoParts\Pages;

use use App\Filament\Tenant\Resources\AutoPartsResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateAutoParts extends CreateRecord
{
    protected static string $resource = AutoPartsResource::class;

    public function getTitle(): string
    {
        return 'Create AutoParts';
    }
}