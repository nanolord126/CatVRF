<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BookOrder\Pages;

use use App\Filament\Tenant\Resources\BookOrderResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewBookOrder extends ViewRecord
{
    protected static string $resource = BookOrderResource::class;

    public function getTitle(): string
    {
        return 'View BookOrder';
    }
}