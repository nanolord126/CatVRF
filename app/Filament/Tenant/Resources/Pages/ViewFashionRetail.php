<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FashionRetail\Pages;

use use App\Filament\Tenant\Resources\FashionRetailResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewFashionRetail extends ViewRecord
{
    protected static string $resource = FashionRetailResource::class;

    public function getTitle(): string
    {
        return 'View FashionRetail';
    }
}