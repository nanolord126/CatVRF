<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Grocery\Pages;

use use App\Filament\Tenant\Resources\GroceryResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateGrocery extends CreateRecord
{
    protected static string $resource = GroceryResource::class;

    public function getTitle(): string
    {
        return 'Create Grocery';
    }
}