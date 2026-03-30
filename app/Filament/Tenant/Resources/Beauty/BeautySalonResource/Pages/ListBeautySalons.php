<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\BeautySalonResource\Pages;

use App\Filament\Tenant\Resources\Beauty\BeautySalonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ListBeautySalons extends ListRecords
{
    protected static string $resource = BeautySalonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $correlationId = (string) Str::uuid();
                    $data['correlation_id'] = $correlationId;
                    $data['tenant_id'] = tenant('id');

                    Log::channel('audit')->info('Creating BeautySalon', [
                        'data' => $data,
                        'tenant_id' => tenant('id'),
                        'correlation_id' => $correlationId,
                    ]);

                    return $data;
                }),
        ];
    }
}
