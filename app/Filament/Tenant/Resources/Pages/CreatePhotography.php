<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Photography\Pages;

use use App\Filament\Tenant\Resources\PhotographyResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreatePhotography extends CreateRecord
{
    protected static string $resource = PhotographyResource::class;

    public function getTitle(): string
    {
        return 'Create Photography';
    }
}