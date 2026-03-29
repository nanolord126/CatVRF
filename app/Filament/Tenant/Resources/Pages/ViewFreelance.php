<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance\Pages;

use use App\Filament\Tenant\Resources\FreelanceResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewFreelance extends ViewRecord
{
    protected static string $resource = FreelanceResource::class;

    public function getTitle(): string
    {
        return 'View Freelance';
    }
}