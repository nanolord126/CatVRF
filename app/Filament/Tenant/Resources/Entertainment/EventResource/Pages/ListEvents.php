<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\EventResource\Pages;

use App\Filament\Tenant\Resources\Entertainment\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

/**
 * КАНОН 2026 — LIST EVENTS PAGE (Entertainment Domain)
 */
final class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->after(function () {
                    Log::channel('audit')->info('Entertainment Event creation started', [
                        'tenant_id' => filament()->getTenant()->id,
                        'user_id' => auth()->id(),
                    ]);
                }),
        ];
    }
}
