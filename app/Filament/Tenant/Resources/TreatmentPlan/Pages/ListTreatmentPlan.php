<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\TreatmentPlan\Pages;
use App\Filament\Tenant\Resources\TreatmentPlanResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsTreatmentPlan extends ListRecords {
    protected static string $resource = TreatmentPlanResource::class;
}
