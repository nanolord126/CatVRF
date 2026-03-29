<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FashionRetail\Pages;

use use App\Filament\Tenant\Resources\FashionRetailResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateFashionRetail extends CreateRecord
{
    protected static string $resource = FashionRetailResource::class;

    public function getTitle(): string
    {
        return 'Create FashionRetail';
    }
}