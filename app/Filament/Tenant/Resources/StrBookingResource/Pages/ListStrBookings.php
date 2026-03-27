<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StrBookingResource\Pages;

use App\Filament\Tenant\Resources\StrBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListStrBookings extends ListRecords
{
    protected static string $resource = StrBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
