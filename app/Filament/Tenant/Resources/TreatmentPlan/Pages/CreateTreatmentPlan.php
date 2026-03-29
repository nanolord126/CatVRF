<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\TreatmentPlan\Pages;
use App\Filament\Tenant\Resources\TreatmentPlanResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordTreatmentPlan extends CreateRecord {
    protected static string $resource = TreatmentPlanResource::class;
}
