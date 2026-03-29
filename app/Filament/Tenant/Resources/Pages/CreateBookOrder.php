<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BookOrder\Pages;

use use App\Filament\Tenant\Resources\BookOrderResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateBookOrder extends CreateRecord
{
    protected static string $resource = BookOrderResource::class;

    public function getTitle(): string
    {
        return 'Create BookOrder';
    }
}