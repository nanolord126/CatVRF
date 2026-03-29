<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FashionProduct\Pages;

use use App\Filament\Tenant\Resources\FashionProductResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewFashionProduct extends ViewRecord
{
    protected static string $resource = FashionProductResource::class;

    public function getTitle(): string
    {
        return 'View FashionProduct';
    }
}