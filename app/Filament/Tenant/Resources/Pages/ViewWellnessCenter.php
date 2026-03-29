<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\WellnessCenter\Pages;

use use App\Filament\Tenant\Resources\WellnessCenterResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewWellnessCenter extends ViewRecord
{
    protected static string $resource = WellnessCenterResource::class;

    public function getTitle(): string
    {
        return 'View WellnessCenter';
    }
}