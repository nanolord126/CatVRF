<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DietPlan\Pages;

use use App\Filament\Tenant\Resources\DietPlanResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditDietPlan extends EditRecord
{
    protected static string $resource = DietPlanResource::class;

    public function getTitle(): string
    {
        return 'Edit DietPlan';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}