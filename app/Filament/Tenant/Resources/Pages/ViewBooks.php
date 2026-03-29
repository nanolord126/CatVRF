<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Books\Pages;

use use App\Filament\Tenant\Resources\BooksResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewBooks extends ViewRecord
{
    protected static string $resource = BooksResource::class;

    public function getTitle(): string
    {
        return 'View Books';
    }
}