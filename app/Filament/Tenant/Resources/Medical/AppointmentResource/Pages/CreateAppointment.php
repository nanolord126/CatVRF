<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\Medical\AppointmentResource\Pages;

use App\Filament\Tenant\Resources\Medical\AppointmentResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateAppointment
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;
}
