<?php

declare(strict_types=1);

namespace App\Domains\Audit\Filament\Resources\AuditLogResource\Pages;

use App\Domains\Audit\Filament\Resources\AuditLogResource;
use App\Domains\Audit\Models\AuditLog;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

/**
 * ViewAuditLog — Filament page for viewing single audit log.
 * Shows detailed information including old/new values.
 */
final class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_correlation')
                ->label('View Related Logs')
                ->url(fn (): string => route('filament.admin.resources.audit-logs.index', [
                    'tableFilters[correlation_id][value]' => $this->record->correlation_id,
                ]))
                ->icon('heroicon-o-link')
                ->color('primary'),
        ];
    }

    protected function getViewData(): array
    {
        /** @var AuditLog $record */
        $record = $this->record;

        return [
            'maskedOldValues' => $record->getMaskedOldValues(),
            'maskedNewValues' => $record->getMaskedNewValues(),
        ];
    }
}
