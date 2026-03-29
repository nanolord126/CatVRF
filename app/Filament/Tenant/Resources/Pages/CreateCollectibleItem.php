<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CollectibleItem\Pages;

use use App\Filament\Tenant\Resources\CollectibleItemResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateCollectibleItem extends CreateRecord
{
    protected static string $resource = CollectibleItemResource::class;

    public function getTitle(): string
    {
        return 'Create CollectibleItem';
    }
}