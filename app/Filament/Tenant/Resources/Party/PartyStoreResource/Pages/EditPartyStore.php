<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party\PartyStoreResource\Pages;


use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\Party\PartyStoreResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

/**
 * Class EditPartyStore
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Party\PartyStoreResource\Pages
 */
final class EditPartyStore extends EditRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = PartyStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();

        return $data;
    }

    protected function afterSave(): void
    {
        \Illuminate\Support\Facades\Log::channel('audit')->info('PartyStore updated', [
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
}
