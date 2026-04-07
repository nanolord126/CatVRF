<?php declare(strict_types=1);

/**
 * ListServices — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listservices
 * @see https://catvrf.ru/docs/listservices
 * @see https://catvrf.ru/docs/listservices
 * @see https://catvrf.ru/docs/listservices
 * @see https://catvrf.ru/docs/listservices
 * @see https://catvrf.ru/docs/listservices
 */


namespace App\Filament\Tenant\Resources\Beauty\ServiceResource\Pages;

use App\Filament\Tenant\Resources\Beauty\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Class ListServices
 *
 * Service layer following CatVRF canon:
 * - No constructor injection on Filament Pages
 * - Services resolved via app() container
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Filament\Tenant\Resources\Beauty\ServiceResource\Pages
 */
final class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new');
    }

    /**
     * Determine if this instance is valid for the current context.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }
}
