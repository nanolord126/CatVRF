<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Filament\Resources\EntertainmentEventResource\Pages;

use App\Domains\Entertainment\Filament\Resources\EntertainmentEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditEntertainmentEvent extends EditRecord
{
    protected static string $resource = EntertainmentEventResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
