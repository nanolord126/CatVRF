<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Food\Pages;

use App\Filament\Tenant\Resources\Food\RestaurantResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateRestaurant extends CreateRecord
{
    protected static string $resource = RestaurantResource::class;
}
