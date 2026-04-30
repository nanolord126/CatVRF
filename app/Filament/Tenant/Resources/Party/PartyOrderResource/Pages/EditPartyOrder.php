<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party\PartyOrderResource\Pages;

use Psr\Log\LoggerInterface;

use App\Filament\Tenant\Resources\Party\PartyOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Class EditPartyOrder
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class EditPartyOrder extends EditRecord
{
    protected static string $resource = PartyOrderResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

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
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();

        return $data;
    }

    protected function afterSave(): void
    {
        $this->log->channel('audit')->$this->logger->info('PartyOrder updated', [
            'order_id' => $this->record->id,
            'status' => $this->record->status,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }
}
