<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TreatmentPlan\Pages;

use use App\Filament\Tenant\Resources\TreatmentPlanResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditTreatmentPlan extends EditRecord
{
    protected static string $resource = TreatmentPlanResource::class;

    public function getTitle(): string
    {
        return 'Edit TreatmentPlan';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}