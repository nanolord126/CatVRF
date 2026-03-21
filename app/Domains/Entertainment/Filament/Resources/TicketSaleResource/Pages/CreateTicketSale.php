<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Filament\Resources\TicketSaleResource\Pages;

use App\Domains\Entertainment\Filament\Resources\TicketSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateTicketSale extends CreateRecord
{
    protected static string $resource = TicketSaleResource::class;
}
