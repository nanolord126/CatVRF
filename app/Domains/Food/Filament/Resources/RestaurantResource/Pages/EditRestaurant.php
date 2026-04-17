<?php declare(strict_types=1);

namespace App\Domains\Food\Filament\Resources\RestaurantResource\Pages;

use App\Domains\Food\Filament\Resources\RestaurantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditRestaurant extends EditRecord
{
    protected static string $resource = RestaurantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
