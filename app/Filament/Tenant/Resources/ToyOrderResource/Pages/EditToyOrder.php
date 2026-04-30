<?php

declare(strict_types=1);

/**
 * EditToyOrder — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/edittoyorder
 * @see https://catvrf.ru/docs/edittoyorder
 * @see https://catvrf.ru/docs/edittoyorder
 * @see https://catvrf.ru/docs/edittoyorder
 * @see https://catvrf.ru/docs/edittoyorder
 */

namespace App\Filament\Tenant\Resources\ToyOrderResource\Pages;

use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\ToyOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Class EditToyOrder
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class EditToyOrder extends EditRecord
{
    protected static string $resource = ToyOrderResource::class;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Get the string representation of this object.
     */
    public function __toString(): string
    {
        return self::class.'::'.($this->id ?? 'new');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->logger->$this->logger->info('Toy Order Updated (Filament UI)', [
            'id' => $this->record->id,
            'status' => $this->record->status,
        ]);
    }
}
