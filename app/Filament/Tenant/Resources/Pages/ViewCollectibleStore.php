<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CollectibleStore\Pages;

use use App\Filament\Tenant\Resources\CollectibleStoreResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewCollectibleStore extends ViewRecord
{
    protected static string $resource = CollectibleStoreResource::class;

    public function getTitle(): string
    {
        return 'View CollectibleStore';
    }
}