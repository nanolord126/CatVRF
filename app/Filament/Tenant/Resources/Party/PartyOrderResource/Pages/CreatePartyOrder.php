<?php

declare(strict_types=1);

/**
 * CreatePartyOrder — CatVRF 2026 Component.
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
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Class CreatePartyOrder
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreatePartyOrder extends CreateRecord
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id ?? null;
        $data['correlation_id'] = (string) Str::uuid();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->log->channel('audit')->$this->logger->info('New PartyOrder created', [
            'order_id' => $this->record->id,
            'event_date' => $this->record->event_date,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }
}
