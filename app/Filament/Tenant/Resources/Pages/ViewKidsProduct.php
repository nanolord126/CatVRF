<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\KidsProduct\Pages;

use use App\Filament\Tenant\Resources\KidsProductResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewKidsProduct extends ViewRecord
{
    protected static string $resource = KidsProductResource::class;

    public function getTitle(): string
    {
        return 'View KidsProduct';
    }
}