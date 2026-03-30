<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ArtisticProjectResource\Pages;

use App\Filament\Tenant\Resources\ArtisticProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ListArtisticProjects extends ListRecords
{
    protected static string $resource = ArtisticProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function afterCreate(): void
    {
        Log::channel('audit')->info('Artistic project listed', [
            'correlation_id' => (string) Str::uuid(),
        ]);
    }
}
