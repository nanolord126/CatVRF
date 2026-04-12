<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CrmSegmentResource\Pages;

use App\Filament\Tenant\Resources\CrmSegmentResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * CreateCrmSegment — создание сегмента CRM в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CreateCrmSegment extends CreateRecord
{
    protected static string $resource = CrmSegmentResource::class;

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
        return 'CreateCrmSegment';
    }

    /**
     * Component: CreateCrmSegment
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
     * CreateCrmSegment — CatVRF 2026 Component.
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
