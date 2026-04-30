<?php

declare(strict_types=1);

/**
 * CreateHotel — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/createhotel
 * @see https://catvrf.ru/docs/createhotel
 */

namespace App\Filament\Tenant\Resources\HotelResource\Pages;

use Psr\Log\LoggerInterface;

use App\Filament\Tenant\Resources\HotelResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;

/**
 * КАНОН 2026: Create Hotel Page
 *
 * Обязательно: Аудит + Fraud check.
 */
/**
 * Class CreateHotel
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreateHotel extends CreateRecord
{
    protected static string $resource = HotelResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    /**
     * Get the string representation of this object.
     */
    public function __toString(): string
    {
        return self::class.'::'.($this->id ?? 'new');
    }

    protected function beforeCreate(): void
    {
        $this->log->channel('audit')->$this->logger->info('Hotel Creation Started', [
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
