<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CollectibleStore\Pages;

use use App\Filament\Tenant\Resources\CollectibleStoreResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateCollectibleStore extends CreateRecord
{
    protected static string $resource = CollectibleStoreResource::class;

    public function getTitle(): string
    {
        return 'Create CollectibleStore';
    }
}