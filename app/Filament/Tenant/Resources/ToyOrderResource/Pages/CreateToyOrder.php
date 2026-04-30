<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ToyOrderResource\Pages;

use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\ToyOrderResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Class CreateToyOrder
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreateToyOrder extends CreateRecord
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['order_number'] = 'TOY-ORD-'.strtoupper(Str::random(10));

        $this->logger->$this->logger->info('Creating Toy Order (Filament UI)', [
            'order_number' => $data['order_number'],
            'cid' => $data['correlation_id'],
        ]);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->logger->$this->logger->info('Toy Order Created (Filament UI)', [
            'id' => $this->record->id,
            'amount' => $this->record->total_amount,
        ]);
    }
}
