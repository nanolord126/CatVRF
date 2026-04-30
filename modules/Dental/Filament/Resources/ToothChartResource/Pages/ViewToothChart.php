<?php

declare(strict_types=1);

namespace Modules\Dental\Filament\Resources\ToothChartResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Dental\Filament\Resources\ToothChartResource;
use Modules\Dental\Livewire\DentalChart;

final class ViewToothChart extends ViewRecord
{
    protected static string $resource = ToothChartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function getViewData(): array
    {
        return [
            'patientId' => $this->record->patient_id,
            'doctorId' => $this->record->doctor_id,
        ];
    }
}
