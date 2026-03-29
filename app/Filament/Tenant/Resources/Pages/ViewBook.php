<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Book\Pages;

use use App\Filament\Tenant\Resources\BookResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewBook extends ViewRecord
{
    protected static string $resource = BookResource::class;

    public function getTitle(): string
    {
        return 'View Book';
    }
}