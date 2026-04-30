<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Filament\Resources\CourierResource\Pages;

use App\Domains\Logistics\Filament\Resources\CourierResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListCouriers extends ListRecords
{
    protected static string $resource = CourierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
