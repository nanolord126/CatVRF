<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VIPBookingResource\Pages;

use App\Filament\Tenant\Resources\VIPBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * ListVIPBookings
 * 
 * Layer 1-3: Filament Pages
 * Список бронирований VIP с tenant scoping.
 * 
 * @version 1.0.0
 * @author CatVRF
 */
final class ListVIPBookings extends ListRecords
{
    protected static string $resource = VIPBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Создать бронирование')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
