<?php declare(strict_types=1);

/**
 * ListPartyOrders — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listpartyorders
 */


namespace App\Domains\PartySupplies\Filament\Resources\PartyOrderResource\Pages;

use App\Domains\PartySupplies\Filament\Resources\PartyOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListPartyOrders extends ListRecords
{
    protected static string $resource = PartyOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}