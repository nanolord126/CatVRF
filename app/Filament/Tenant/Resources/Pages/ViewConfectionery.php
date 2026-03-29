<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Confectionery\Pages;

use use App\Filament\Tenant\Resources\ConfectioneryResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewConfectionery extends ViewRecord
{
    protected static string $resource = ConfectioneryResource::class;

    public function getTitle(): string
    {
        return 'View Confectionery';
    }
}