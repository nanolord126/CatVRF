<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Food\Pages;

use use App\Filament\Tenant\Resources\FoodResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateFood extends CreateRecord
{
    protected static string $resource = FoodResource::class;

    public function getTitle(): string
    {
        return 'Create Food';
    }
}