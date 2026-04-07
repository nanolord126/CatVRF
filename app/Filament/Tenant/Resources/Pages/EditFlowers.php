<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\FlowersResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Class EditFlowers
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class EditFlowers extends EditRecord
{
    protected static string $resource = FlowersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Удалить B2B-магазин цветов?')
                ->modalDescription('Это действие необратимо.')
                ->modalSubmitActionLabel('Да, удалить'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();
        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $logger = app(LoggerInterface::class);
        $logger->info('Flower B2B storefront updated', [
            'storefront_id'  => $record->id,
            'company_name'   => $record->company_name,
            'is_active'      => $record->is_active,
            'correlation_id' => $record->correlation_id,
        ]);
    }
}
