<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party\PartyStoreResource\Pages;


use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\Party\PartyStoreResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

/**
 * Class CreatePartyStore
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Party\PartyStoreResource\Pages
 */
final class CreatePartyStore extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = PartyStoreResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id ?? null;
        $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();

        return $data;
    }

    protected function afterCreate(): void
    {
        \Illuminate\Support\Facades\Log::channel('audit')->info('New PartyStore created', [
            'store_id' => $this->record->id,
            'correlation_id' => $this->record->correlation_id,
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

    /**
     * Determine if this instance is valid for the current context.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }
}
