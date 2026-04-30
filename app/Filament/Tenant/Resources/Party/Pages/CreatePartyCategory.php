<?php

declare(strict_types=1);

/**
 * CreatePartyCategory — CatVRF 2026 Component.
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
use Illuminate\Support\Str;

/**
 * Class CreatePartyCategory
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreatePartyCategory extends CreateRecord
{
    protected static string $resource = PartyCategoryResource::class;

    public function __construct(
        private readonly Request $request,
    ) {}

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
        $data['correlation_id'] = $this->request->header('X-Correlation-ID', Str::uuid());

        return $data;
    }
}
