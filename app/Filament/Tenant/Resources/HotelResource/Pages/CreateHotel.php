<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HotelResource\Pages;

use App\Filament\Tenant\Resources\HotelResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

/**
 * КАНОН 2026: Create Hotel Page
 * 
 * Обязательно: Аудит + Fraud check.
 */
final class CreateHotel extends CreateRecord
{
    protected static string $resource = HotelResource::class;

    protected function beforeCreate(): void
    {
        Log::channel('audit')->info('Hotel Creation Started', [
            'raw_data' => $this->data,
        ]);
        
        // Fraud check placeholder
        // \App\Services\FraudControlService::check('hotel_creation');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
