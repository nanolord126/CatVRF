<?php

declare(strict_types=1);


namespace App\Domains\EventPlanning\Entertainment\Filament\Resources\TicketSaleResource\Pages;

use App\Domains\EventPlanning\Entertainment\Filament\Resources\TicketSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateTicketSale
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateTicketSale extends CreateRecord
{
    protected static string $resource = TicketSaleResource::class;
}
