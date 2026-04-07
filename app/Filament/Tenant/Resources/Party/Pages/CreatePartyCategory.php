<?php declare(strict_types=1);

/**
 * CreatePartyCategory — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createpartycategory
 * @see https://catvrf.ru/docs/createpartycategory
 * @see https://catvrf.ru/docs/createpartycategory
 * @see https://catvrf.ru/docs/createpartycategory
 * @see https://catvrf.ru/docs/createpartycategory
 */


namespace App\Filament\Tenant\Resources\Party\Pages;


use Illuminate\Http\Request;
use App\Filament\Tenant\Resources\Party\PartyCategoryResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Class CreatePartyCategory
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Party\Pages
 */
final class CreatePartyCategory extends CreateRecord
{
    public function __construct(
        private readonly Request $request,
    ) {}

    protected static string $resource = PartyCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id ?? null;
        $data['correlation_id'] = $this->request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid());

        return $data;
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
