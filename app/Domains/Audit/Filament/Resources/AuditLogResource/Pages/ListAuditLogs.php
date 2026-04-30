<?php

declare(strict_types=1);

namespace App\Domains\Audit\Filament\Resources\AuditLogResource\Pages;

use App\Domains\Audit\Filament\Resources\AuditLogResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * ListAuditLogs — Filament page for listing audit logs.
 * Provides filtering, search, and bulk actions.
 */
final class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(), // Audit logs are read-only
        ];
    }
}
