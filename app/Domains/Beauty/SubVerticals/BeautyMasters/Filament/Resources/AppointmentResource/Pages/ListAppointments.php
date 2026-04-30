<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\AppointmentResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListAppointments extends ListRecords
{
    protected static string $resource = \Modules\BeautyMasters\Filament\Resources\AppointmentResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
