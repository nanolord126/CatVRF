<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Program\Pages;

use use App\Filament\Tenant\Resources\ProgramResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewProgram extends ViewRecord
{
    protected static string $resource = ProgramResource::class;

    public function getTitle(): string
    {
        return 'View Program';
    }
}