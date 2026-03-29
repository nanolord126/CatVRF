<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FashionStore\Pages;

use use App\Filament\Tenant\Resources\FashionStoreResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewFashionStore extends ViewRecord
{
    protected static string $resource = FashionStoreResource::class;

    public function getTitle(): string
    {
        return 'View FashionStore';
    }
}