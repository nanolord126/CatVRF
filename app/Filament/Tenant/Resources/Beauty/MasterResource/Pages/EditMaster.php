<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\MasterResource\Pages;

use App\Filament\Tenant\Resources\Beauty\MasterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * EditMaster — Filament Page (Layer 9).
 *
 * Tenant-scoped master editing with correlation_id tracing.
 * No constructor injection — services resolved via app().
 *
 * @package App\Filament\Tenant\Resources\Beauty\MasterResource\Pages
 */
final class EditMaster extends EditRecord
{
    protected static string $resource = MasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = Str::uuid()->toString();

        app(LoggerInterface::class)->info('Beauty: обновление мастера', [
            'tenant_id'      => filament()->getTenant()?->id,
            'record_id'      => $this->record->getKey(),
            'correlation_id' => $data['correlation_id'],
        ]);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
