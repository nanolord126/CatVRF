<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\TreatmentPlan\Pages;
use App\Filament\Tenant\Resources\TreatmentPlanResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordTreatmentPlan extends EditRecord {
    protected static string $resource = TreatmentPlanResource::class;
}
