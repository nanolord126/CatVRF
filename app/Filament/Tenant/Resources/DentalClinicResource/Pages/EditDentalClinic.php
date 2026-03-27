<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalClinicResource\Pages;

use App\Filament\Tenant\Resources\DentalClinicResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Edit Page for Dental Clinics.
 * Implements CANON 2026: Transactions & Audit.
 */
final class EditDentalClinic extends EditRecord
{
    protected static string $resource = DentalClinicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($record, $data) {
            $oldName = $record->name;
            $record = parent::handleRecordUpdate($record, $data);

            Log::channel('audit')->info('Dental Clinic Updated', [
                'clinic_id' => $record->id,
                'old_name' => $oldName,
                'new_name' => $record->name,
                'correlation_id' => $record->correlation_id
            ]);

            return $record;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
