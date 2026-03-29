<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VeganProduct\Pages;

use use App\Filament\Tenant\Resources\VeganProductResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewVeganProduct extends ViewRecord
{
    protected static string $resource = VeganProductResource::class;

    public function getTitle(): string
    {
        return 'View VeganProduct';
    }
}