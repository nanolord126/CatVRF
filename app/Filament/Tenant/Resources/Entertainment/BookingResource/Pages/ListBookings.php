<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\BookingResource\Pages;

use App\Filament\Tenant\Resources\Entertainment\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

/**
 * КАНОН 2026 — LIST BOOKINGS PAGE (Entertainment Domain)
 */
final class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->after(function () {
                    Log::channel('audit')->info('Entertainment Booking creation started', [
                        'tenant_id' => filament()->getTenant()->id,
                        'user_id' => auth()->id(),
                    ]);
                }),
        ];
    }
}
