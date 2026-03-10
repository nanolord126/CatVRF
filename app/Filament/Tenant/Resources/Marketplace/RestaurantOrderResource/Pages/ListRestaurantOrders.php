<?php

namespace App\Filament\Tenant\Resources\Marketplace\RestaurantOrderResource\Pages;

use App\Filament\Tenant\Resources\Marketplace\RestaurantOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantOrders extends ListRecords
{
    protected static string $resource = RestaurantOrderResource::class;
}
