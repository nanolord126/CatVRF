<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HealthyFoodResource\Pages;

use App\Filament\Tenant\Resources\HealthyFoodResource;
use Filament\Resources\Pages\EditRecord;

final class EditHealthyFood extends EditRecord
{
    protected static string $resource = HealthyFoodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
