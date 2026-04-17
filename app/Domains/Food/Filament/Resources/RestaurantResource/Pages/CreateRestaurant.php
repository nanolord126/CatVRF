<?php declare(strict_types=1);

namespace App\Domains\Food\Filament\Resources\RestaurantResource\Pages;

use App\Domains\Food\Filament\Resources\RestaurantResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateRestaurant extends CreateRecord
{
    protected static string $resource = RestaurantResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
