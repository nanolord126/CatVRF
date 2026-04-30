<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VIPBookingResource\Pages;

use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\VIPBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Class EditVIPBooking
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class EditVIPBooking extends EditRecord
{
    protected static string $resource = VIPBookingResource::class;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

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

        $this->logger->$this->logger->info('Editing VIP Booking via Filament', [
            'booking_id' => $this->record->id,
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
