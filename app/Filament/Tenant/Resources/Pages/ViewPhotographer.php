<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Photographer\Pages;

use use App\Filament\Tenant\Resources\PhotographerResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewPhotographer extends ViewRecord
{
    protected static string $resource = PhotographerResource::class;

    public function getTitle(): string
    {
        return 'View Photographer';
    }
}