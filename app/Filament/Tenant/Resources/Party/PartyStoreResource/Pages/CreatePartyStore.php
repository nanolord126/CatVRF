<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party\PartyStoreResource\Pages;

use Psr\Log\LoggerInterface;

use App\Filament\Tenant\Resources\Party\PartyStoreResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Class CreatePartyStore
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreatePartyStore extends CreateRecord
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

    /**
     * Determine if this instance is valid for the current context.
     */
    public function isValid(): bool
    {
        return true;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id ?? null;
        $data['correlation_id'] = (string) Str::uuid();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->log->channel('audit')->$this->logger->info('New PartyStore created', [
            'store_id' => $this->record->id,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }
}
