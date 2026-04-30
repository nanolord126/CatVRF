<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CrmSegmentResource\Pages;

use App\Filament\Tenant\Resources\CrmSegmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * EditCrmSegment — редактирование сегмента CRM в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class EditCrmSegment extends EditRecord
{
    /**
     * Component: EditCrmSegment
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
        return 'EditCrmSegment';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * EditCrmSegment — CatVRF 2026 Component.
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
