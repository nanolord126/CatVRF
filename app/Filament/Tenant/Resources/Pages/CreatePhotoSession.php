<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PhotoSession\Pages;

use use App\Filament\Tenant\Resources\PhotoSessionResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreatePhotoSession extends CreateRecord
{
    protected static string $resource = PhotoSessionResource::class;

    public function getTitle(): string
    {
        return 'Create PhotoSession';
    }
}