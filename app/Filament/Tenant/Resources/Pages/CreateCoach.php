<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Coach\Pages;

use use App\Filament\Tenant\Resources\CoachResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateCoach extends CreateRecord
{
    protected static string $resource = CoachResource::class;

    public function getTitle(): string
    {
        return 'Create Coach';
    }
}