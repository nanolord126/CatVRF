<?php

declare(strict_types=1);

/**
 * EditDentalConsumable — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/editdentalconsumable
 * @see https://catvrf.ru/docs/editdentalconsumable
 */

namespace App\Filament\Tenant\Resources\Pages;

use Psr\Log\LoggerInterface;

use App\Filament\Tenant\Resources\DentalConsumableResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Class EditDentalConsumable
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class EditDentalConsumable extends EditRecord
{
    protected static string $resource = DentalConsumableResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()->requiresConfirmation()
                ->modalHeading('Удалить расходник?')
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
        $this->log->channel('audit')->$this->logger->info('DentalConsumable updated', [
            'consumable_id'  => $this->record->id,
            'name'           => $this->record->name,
            'current_stock'  => $this->record->current_stock,
            'tenant_id'      => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }
}
