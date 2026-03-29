<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HealthyFood\Pages;

use use App\Filament\Tenant\Resources\HealthyFoodResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditHealthyFood extends EditRecord
{
    protected static string $resource = HealthyFoodResource::class;

    public function getTitle(): string
    {
        return 'Edit HealthyFood';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}