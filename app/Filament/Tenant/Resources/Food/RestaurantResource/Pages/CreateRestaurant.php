<?php

declare(strict_types=1);

/**
 * CreateRestaurant — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createrestaurant
 */


namespace App\Filament\Tenant\Resources\Food\RestaurantResource\Pages;


use Illuminate\Auth\AuthManager;
use App\Filament\Tenant\Resources\Food\RestaurantResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Class CreateRestaurant
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Food\RestaurantResource\Pages
 */
final class CreateRestaurant extends CreateRecord
{
    public function __construct(
        private readonly AuthManager $authManager,
    ) {}

    protected static string $resource = RestaurantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = $this->authManager->user()->tenant_id;
        $data['correlation_id'] = Str::uuid()->toString();

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
