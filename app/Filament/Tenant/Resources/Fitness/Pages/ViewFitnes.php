<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Fitness\Pages;

use App\Filament\Tenant\Resources\Fitness\FitnessResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

final class ViewFitnes extends ViewRecord
{
    protected static string $resource = FitnessResource::class;

    protected function afterLoad(): void
    {
        Log::channel('audit')->info('Fitness record viewed', [
            'record_id' => $this->record->id,
            'uuid' => $this->record->uuid,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function render()
    {
        Log::channel('audit')->debug('ViewFitnes page rendered', [
            'record_id' => $this->record->id,
            'user_id' => auth()->id(),
        ]);

        return parent::render();
    }
}
