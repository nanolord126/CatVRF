<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoRepairOrder\Pages;

use use App\Filament\Tenant\Resources\AutoRepairOrderResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditAutoRepairOrder extends EditRecord
{
    protected static string $resource = AutoRepairOrderResource::class;

    public function getTitle(): string
    {
        return 'Edit AutoRepairOrder';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}