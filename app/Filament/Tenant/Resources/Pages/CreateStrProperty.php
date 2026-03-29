<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StrProperty\Pages;

use use App\Filament\Tenant\Resources\StrPropertyResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateStrProperty extends CreateRecord
{
    protected static string $resource = StrPropertyResource::class;

    public function getTitle(): string
    {
        return 'Create StrProperty';
    }
}