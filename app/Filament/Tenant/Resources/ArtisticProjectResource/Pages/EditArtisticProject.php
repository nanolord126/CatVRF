<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ArtisticProjectResource\Pages;

use App\Filament\Tenant\Resources\ArtisticProjectResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class EditArtisticProject extends EditRecord
{
    protected static string $resource = ArtisticProjectResource::class;

    protected function afterSave(): void
    {
        Log::channel('audit')->info('Artistic project updated via Filament', [
            'record_id' => $this->record->id,
            'correlation_id' => (string) Str::uuid(),
        ]);
    }
}
