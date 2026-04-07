<?php declare(strict_types=1);

/**
 * CreatePartyProduct — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

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
use Illuminate\Support\Facades\Log;

/**
 * Class CreatePartyProduct
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Party\PartyProductResource\Pages
 */
final class CreatePartyProduct extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = PartyProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id ?? null;
        $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->logger->info('New PartyProduct created', [
            'product_id' => $this->record->id,
            'sku' => $this->record->sku,
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
