<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Cosmetics\Pages;

use use App\Filament\Tenant\Resources\CosmeticsResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewCosmetics extends ViewRecord
{
    protected static string $resource = CosmeticsResource::class;

    public function getTitle(): string
    {
        return 'View Cosmetics';
    }
}