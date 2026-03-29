<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Coach\Pages;

use use App\Filament\Tenant\Resources\CoachResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewCoach extends ViewRecord
{
    protected static string $resource = CoachResource::class;

    public function getTitle(): string
    {
        return 'View Coach';
    }
}