<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Book\Pages;

use use App\Filament\Tenant\Resources\BookResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateBook extends CreateRecord
{
    protected static string $resource = BookResource::class;

    public function getTitle(): string
    {
        return 'Create Book';
    }
}