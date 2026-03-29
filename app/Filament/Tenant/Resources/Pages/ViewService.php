<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Service\Pages;

use use App\Filament\Tenant\Resources\ServiceResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewService extends ViewRecord
{
    protected static string $resource = ServiceResource::class;

    public function getTitle(): string
    {
        return 'View Service';
    }
}