<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Food\RestaurantResource\Pages;
use App\Filament\Tenant\Resources\Food\RestaurantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListRestaurants extends ListRecords { protected static string $resource = RestaurantResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
