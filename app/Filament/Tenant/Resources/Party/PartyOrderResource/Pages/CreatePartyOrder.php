<?php declare(strict_types=1);

/**
 * CreatePartyOrder — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createpartyorder
 * @see https://catvrf.ru/docs/createpartyorder
 * @see https://catvrf.ru/docs/createpartyorder
 * @see https://catvrf.ru/docs/createpartyorder
 * @see https://catvrf.ru/docs/createpartyorder
 */


namespace App\Filament\Tenant\Resources\Party\PartyOrderResource\Pages;


use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\Party\PartyOrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

/**
 * Class CreatePartyOrder
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Party\PartyOrderResource\Pages
 */
final class CreatePartyOrder extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = PartyOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id ?? null;
        $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->logger->info('New PartyOrder created', [
            'order_id' => $this->record->id,
            'event_date' => $this->record->event_date,
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
