<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PhotoSessionResource\Pages;

use App\Filament\Tenant\Resources\PhotoSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePhotoSession extends CreateRecord
{
    protected static string $resource = PhotoSessionResource::class;
}
