<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto\AutoRepairOrderResource\Pages;

use App\Filament\Tenant\Resources\Auto\AutoRepairOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditAutoRepairOrder extends EditRecord
{
    protected static string $resource = AutoRepairOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['total_cost_kopecks'] = ($data['labor_cost_kopecks'] ?? 0) + ($data['parts_cost_kopecks'] ?? 0);
        return $data;
    }
}
