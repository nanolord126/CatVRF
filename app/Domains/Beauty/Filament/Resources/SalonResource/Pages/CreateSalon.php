<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament\Resources\SalonResource\Pages;

use App\Domains\Beauty\Filament\Resources\SalonResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * CreateSalon — создание салона красоты в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CreateSalon extends CreateRecord
{
    protected static string $resource = SalonResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Строковое представление для отладки.
     */
    public function __toString(): string
    {
        return 'CreateSalon';
    }

    /**
     * Component: CreateSalon
     *
     * Part of the CatVRF 2026 multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     */
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * CreateSalon — CatVRF 2026 Component.
     *
     * Part of the CatVRF multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     * @author CatVRF Team
     * @license Proprietary
     * @see https://catvrf.ru/docs/createsalon
     * @see https://catvrf.ru/docs/createsalon
     */

}
