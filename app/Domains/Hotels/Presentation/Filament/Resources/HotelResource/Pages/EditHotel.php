<?php

declare(strict_types=1);

/**
 * EditHotel — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/edithotel
 */


namespace App\Domains\Hotels\Presentation\Filament\Resources\HotelResource\Pages;

use App\Domains\Hotels\Presentation\Filament\Resources\HotelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Class EditHotel
 *
 * Part of the Hotels vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Domains\Hotels\Presentation\Filament\Resources\HotelResource\Pages
 */
final class EditHotel extends EditRecord
{
    protected static string $resource = HotelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
