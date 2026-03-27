<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Music\MusicStudioResource\Pages;

use App\Filament\Tenant\Resources\Music\MusicStudioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * EditMusicStudio page component.
 */
final class EditMusicStudio extends EditRecord
{
    protected static string $resource = MusicStudioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Re-generate correlation_id for audit.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();

        return $data;
    }

    /**
     * Handle updates in a transaction.
     */
    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($record, $data) {
            $record->update($data);

            Log::channel('audit')->info('Music studio updated via UI', [
                'studio_id' => $record->id,
                'tenant_id' => $record->tenant_id,
                'correlation_id' => $record->correlation_id,
                'updated_by' => auth()->id(),
            ]);

            return $record;
        });
    }

    /**
     * Redirect to index page after save.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
