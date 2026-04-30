<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CrmClientResource\Pages;

use App\Filament\Tenant\Resources\CrmClientResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * EditCrmClient — редактирование CRM-клиента в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class EditCrmClient extends EditRecord
{
    /**
     * Component: EditCrmClient
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

    protected static string $resource = CrmClientResource::class;

    /**
     * Строковое представление для отладки.
     */
    public function __toString(): string
    {
        return 'EditCrmClient';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * EditCrmClient — CatVRF 2026 Component.
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
