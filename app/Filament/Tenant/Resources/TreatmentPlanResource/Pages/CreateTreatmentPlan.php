<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TreatmentPlanResource\Pages;

use App\Filament\Tenant\Resources\TreatmentPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateTreatmentPlan extends CreateRecord
{
    protected static string $resource = TreatmentPlanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
