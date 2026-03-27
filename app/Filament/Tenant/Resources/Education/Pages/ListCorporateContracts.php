<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Education\Pages;

use App\Filament\Tenant\Resources\Education\CorporateContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ListCorporateContracts.
 * Канон 2026: List view с Audit Log и Correlation ID.
 */
final class ListCorporateContracts extends ListRecords
{
    protected static string $resource = CorporateContractResource::class;

    /**
     * Кнопка создания нового контракта (Agreement Construction).
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Construction Engagement (B2B)')
                ->icon('heroicon-o-document-plus')
                ->successNotificationTitle('Corporate contract constructed and pending signature.'),
        ];
    }

    /**
     * Логирование в аудит-канал.
     */
    public function mount(): void
    {
        parent::mount();

        Log::channel('audit')->info('B2B Education: Viewing contracts list', [
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
            'correlation_id' => (string) Str::uuid(),
        ]);
    }
}
