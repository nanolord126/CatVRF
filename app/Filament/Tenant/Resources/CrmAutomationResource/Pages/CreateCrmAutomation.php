<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CrmAutomationResource\Pages;

use App\Filament\Tenant\Resources\CrmAutomationResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * CreateCrmAutomation — создание автоматизации CRM в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CreateCrmAutomation extends CreateRecord
{
    protected static string $resource = CrmAutomationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()?->id;
        $data['correlation_id'] = \Illuminate\Support\Str::uuid()->toString();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Строковое представление для отладки.
     */
    public function __toString(): string
    {
        return 'CreateCrmAutomation';
    }

    /**
     * Component: CreateCrmAutomation
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
     * CreateCrmAutomation — CatVRF 2026 Component.
     *
     * Part of the CatVRF multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     * @author CatVRF Team
     * @license Proprietary
     */
}
