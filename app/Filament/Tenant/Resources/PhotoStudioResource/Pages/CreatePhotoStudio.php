<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PhotoStudioResource\Pages;

use App\Filament\Tenant\Resources\PhotoStudioResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePhotoStudio extends CreateRecord
{
    protected static string $resource = PhotoStudioResource::class;
}
