<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StrBooking\Pages;

use use App\Filament\Tenant\Resources\StrBookingResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditStrBooking extends EditRecord
{
    protected static string $resource = StrBookingResource::class;

    public function getTitle(): string
    {
        return 'Edit StrBooking';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}