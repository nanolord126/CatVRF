<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ToyOrderResource\Pages;


use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\ToyOrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class CreateToyOrder
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\ToyOrderResource\Pages
 */
final class CreateToyOrder extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = ToyOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['order_number'] = 'TOY-ORD-' . strtoupper(Str::random(10));

        $this->logger->info('Creating Toy Order (Filament UI)', [
            'order_number' => $data['order_number'],
            'cid' => $data['correlation_id']
        ]);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->logger->info('Toy Order Created (Filament UI)', [
            'id' => $this->record->id,
            'amount' => $this->record->total_amount
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
