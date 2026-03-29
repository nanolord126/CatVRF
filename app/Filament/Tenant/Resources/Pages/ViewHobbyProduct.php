<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HobbyProduct\Pages;

use use App\Filament\Tenant\Resources\HobbyProductResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewHobbyProduct extends ViewRecord
{
    protected static string $resource = HobbyProductResource::class;

    public function getTitle(): string
    {
        return 'View HobbyProduct';
    }
}