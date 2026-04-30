<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\Food\Filament\Resources\DishResource\Pages;

use App\Domains\Food\Filament\Resources\DishResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateDish extends CreateRecord
{
    protected static string $resource = DishResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
