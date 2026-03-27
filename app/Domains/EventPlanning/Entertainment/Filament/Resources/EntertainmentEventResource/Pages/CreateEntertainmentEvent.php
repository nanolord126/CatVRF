<?php

declare(strict_types=1);


namespace App\Domains\EventPlanning\Entertainment\Filament\Resources\EntertainmentEventResource\Pages;

use App\Domains\EventPlanning\Entertainment\Filament\Resources\EntertainmentEventResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateEntertainmentEvent
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateEntertainmentEvent extends CreateRecord
{
    protected static string $resource = EntertainmentEventResource::class;
}
