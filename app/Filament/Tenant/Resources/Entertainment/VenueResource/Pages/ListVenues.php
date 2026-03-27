<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\VenueResource\Pages;

use App\Filament\Tenant\Resources\Entertainment\VenueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

/**
 * КАНОН 2026 — LIST VENUES PAGE (Entertainment Domain)
 */
final class ListVenues extends ListRecords
{
    protected static string $resource = VenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->after(function () {
                    Log::channel('audit')->info('Venue creation started', [
                        'tenant_id' => filament()->getTenant()->id,
                        'user_id' => auth()->id(),
                    ]);
                }),
        ];
    }
}
