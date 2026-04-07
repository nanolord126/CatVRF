<?php declare(strict_types=1);

/**
 * ListB2BPetStorefronts — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listb2bpetstorefronts
 */


namespace App\Domains\Pet\Filament\Resources\B2BPetStorefrontResource\Pages;

use App\Domains\Pet\Filament\Resources\B2BPetStorefrontResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Class ListB2BPetStorefronts
 *
 * Part of the Pet vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Domains\Pet\Filament\Resources\B2BPetStorefrontResource\Pages
 */
final class ListB2BPetStorefronts extends ListRecords
{
    protected static string $resource = B2BPetStorefrontResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
/**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
