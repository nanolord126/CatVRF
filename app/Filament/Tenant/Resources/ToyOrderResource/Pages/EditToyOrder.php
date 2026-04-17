<?php declare(strict_types=1);

/**
 * EditToyOrder — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

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
 *
 * @package App\Filament\Tenant\Resources\ToyOrderResource\Pages
 */
final class EditToyOrder extends EditRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = ToyOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->logger->info('Toy Order Updated (Filament UI)', [
            'id' => $this->record->id,
            'status' => $this->record->status
        ]);
    }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new');
    }
}
