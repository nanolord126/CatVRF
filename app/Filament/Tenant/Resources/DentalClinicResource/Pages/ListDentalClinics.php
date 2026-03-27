<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalClinicResource\Pages;

use App\Filament\Tenant\Resources\DentalClinicResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

/**
 * List Page for Dental Clinics.
 * Implements CANON 2026 Audit Logging.
 */
final class ListDentalClinics extends ListRecords
{
    protected static string $resource = DentalClinicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label('Register New Clinic'),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        Log::channel('audit')->info('Dental Clinic Directory accessed', [
            'tenant_id' => tenant()->id ?? 'system',
            'user_id' => auth()->id(),
            'correlation_id' => request()->header('X-Correlation-ID')
        ]);
    }
}
