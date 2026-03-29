<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PhotoStudio\Pages;

use use App\Filament\Tenant\Resources\PhotoStudioResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewPhotoStudio extends ViewRecord
{
    protected static string $resource = PhotoStudioResource::class;

    public function getTitle(): string
    {
        return 'View PhotoStudio';
    }
}