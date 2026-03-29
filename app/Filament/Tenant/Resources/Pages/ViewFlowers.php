<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\Pages;

use use App\Filament\Tenant\Resources\FlowersResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewFlowers extends ViewRecord
{
    protected static string $resource = FlowersResource::class;

    public function getTitle(): string
    {
        return 'View Flowers';
    }
}