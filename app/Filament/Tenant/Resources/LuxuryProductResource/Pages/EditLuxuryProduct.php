<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\LuxuryProductResource\Pages;

use App\Filament\Tenant\Resources\LuxuryProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * EditLuxuryProduct
 * 
 * Layer 1-3: Filament Pages
 * Редактирование товара с аудитом.
 * 
 * @version 1.0.0
 * @author CatVRF
 */
final class EditLuxuryProduct extends EditRecord
{
    protected static string $resource = LuxuryProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
            Actions\ViewAction::make()
                ->icon('heroicon-o-eye'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();

        Log::channel('audit')->info('Editing Luxury Product via Filament', [
            'product_id' => $this->record->id,
            'user_id' => auth()->id(),
            'correlation_id' => $data['correlation_id'],
        ]);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
