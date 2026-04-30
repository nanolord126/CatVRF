<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\AppointmentResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateAppointment extends CreateRecord
{
    protected static string $resource = \Modules\BeautyMasters\Filament\Resources\AppointmentResource::class;
}
