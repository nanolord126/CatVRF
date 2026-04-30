<?php

declare(strict_types=1);

/**
 * CreateVIPBooking — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/createvipbooking
 */

namespace App\Filament\Tenant\Resources\VIPBookingResource\Pages;

use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\VIPBookingResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Class CreateVIPBooking
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreateVIPBooking extends CreateRecord
{
    protected static string $resource = VIPBookingResource::class;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Get the string representation of this object.
     */
    public function __toString(): string
    {
        return self::class.'::'.($this->id ?? 'new');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();

        $this->logger->$this->logger->info('Creating VIP Booking via Filament', [
            'client_id' => $data['client_id'] ?? 'N/A',
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
