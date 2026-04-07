<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use App\Filament\Tenant\Resources\Beauty\BeautyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * EditBeauty — Filament Page (Layer 9).
 *
 * Tenant-scoped salon editing with transaction + audit logging.
 * No constructor injection — services resolved via app().
 *
 * @package App\Filament\Tenant\Resources\Beauty\Pages
 */
final class EditBeauty extends EditRecord
{
    protected static string $resource = BeautyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Удалить')
                ->icon('heroicon-m-trash'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        app(DatabaseManager::class)->transaction(function () use (&$data): void {
            $data['correlation_id'] = Str::uuid()->toString();
            $data['tenant_id']      = filament()->getTenant()?->id;

            app(LoggerInterface::class)->info('Beauty salon updated', [
                'user_id'        => filament()->auth()->id(),
                'correlation_id' => $data['correlation_id'],
                'tenant_id'      => $data['tenant_id'],
                'salon_id'       => $this->record->id,
                'salon_name'     => $data['name'] ?? null,
            ]);
        });

        return $data;
    }

    protected function afterSave(): void
    {
        app(LoggerInterface::class)->info('Beauty edit page saved', [
            'record_id' => $this->record->id,
            'user_id'   => filament()->auth()->id(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
