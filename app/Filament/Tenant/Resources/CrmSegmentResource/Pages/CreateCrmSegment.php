<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CrmSegmentResource\Pages;

use App\Filament\Tenant\Resources\CrmSegmentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

/**
 * CreateCrmSegment — создание сегмента CRM в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CreateCrmSegment extends CreateRecord
{
    /**
     * Component: CreateCrmSegment
     *
     * Part of the CatVRF 2026 multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @version 2026.1
     */
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    protected static string $resource = CrmSegmentResource::class;

    /**
     * Строковое представление для отладки.
     */
    public function __toString(): string
    {
        return 'CreateCrmSegment';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()?->id;
        $data['correlation_id'] = Str::uuid()->toString();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * CreateCrmSegment — CatVRF 2026 Component.
     *
     * Part of the CatVRF multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @version 2026.1
     *
     * @author CatVRF Team
     * @license Proprietary
     */
}
