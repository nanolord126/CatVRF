<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ArtisticProjectResource\Pages;

use App\Filament\Tenant\Resources\ArtisticProjectResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ViewArtisticProject extends ViewRecord
{
    protected static string $resource = ArtisticProjectResource::class;

    protected function afterMount(): void
    {
        Log::channel('audit')->info('Artistic project viewed via Filament', [
            'record_id' => $this->record->id,
            'correlation_id' => (string) Str::uuid(),
        ]);
    }
}
