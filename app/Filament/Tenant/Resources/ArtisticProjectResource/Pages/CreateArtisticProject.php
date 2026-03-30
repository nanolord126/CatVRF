<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ArtisticProjectResource\Pages;

use App\Filament\Tenant\Resources\ArtisticProjectResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CreateArtisticProject extends CreateRecord
{
    protected static string $resource = ArtisticProjectResource::class;

    protected function afterCreate(): void
    {
        Log::channel('audit')->info('Artistic project created via Filament', [
            'record_id' => $this->record->id,
            'correlation_id' => (string) Str::uuid(),
        ]);
    }
}
