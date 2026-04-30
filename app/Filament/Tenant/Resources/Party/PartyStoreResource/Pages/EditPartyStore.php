<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party\PartyStoreResource\Pages;

use Psr\Log\LoggerInterface;

use App\Filament\Tenant\Resources\Party\PartyStoreResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Class EditPartyStore
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class EditPartyStore extends EditRecord
{
    protected static string $resource = PartyStoreResource::class;

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
        $this->log->channel('audit')->$this->logger->info('PartyStore updated', [
            'store_id' => $this->record->id,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }
}
