<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\LuxuryProductResource\Pages;

use App\Filament\Tenant\Resources\LuxuryProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CreateLuxuryProduct
 * 
 * Layer 1-3: Filament Pages
 * Создание товара с аудитом и correlation_id.
 * 
 * @version 1.0.0
 * @author CatVRF
 */
final class CreateLuxuryProduct extends CreateRecord
{
    protected static string $resource = LuxuryProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();
        
        Log::channel('audit')->info('Creating Luxury Product via Filament', [
            'sku' => $data['sku'] ?? 'N/A',
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
