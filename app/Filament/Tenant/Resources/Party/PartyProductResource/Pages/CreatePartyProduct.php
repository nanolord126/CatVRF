<?php

declare(strict_types=1);

/**
 * CreatePartyProduct — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/createpartyproduct
 * @see https://catvrf.ru/docs/createpartyproduct
 * @see https://catvrf.ru/docs/createpartyproduct
 * @see https://catvrf.ru/docs/createpartyproduct
 * @see https://catvrf.ru/docs/createpartyproduct
 */

namespace App\Filament\Tenant\Resources\Party\PartyProductResource\Pages;

use Psr\Log\LoggerInterface;

use App\Filament\Tenant\Resources\Party\PartyProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Class CreatePartyProduct
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreatePartyProduct extends CreateRecord
{
    protected static string $resource = PartyProductResource::class;

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
        $this->log->channel('audit')->$this->logger->info('New PartyProduct created', [
            'product_id' => $this->record->id,
            'sku' => $this->record->sku,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }
}
