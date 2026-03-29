<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FarmDirect\Pages;

use App\Filament\Tenant\Resources\FarmDirect\FarmDirectResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\{Log,DB};
use Illuminate\Support\Str;

final class CreateFarmDirect extends CreateRecord
{
    protected static string $resource = FarmDirectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = Str::uuid()->toString();
        
        DB::transaction(function () use (&$data, $correlationId) {
            $data['correlation_id'] = $correlationId;
            $data['tenant_id'] = filament()->getTenant()->id;
            $data['uuid'] = Str::uuid()->toString();

            Log::channel('audit')->info('FarmDirect creation form submitted', [
                'correlation_id' => $correlationId,
                'tenant_id' => $data['tenant_id'],
                'user_id' => auth()->id(),
            ]);
        });

        return $data;
    }

    protected function afterCreate(): void
    {
        Log::channel('audit')->info('FarmDirect record created successfully', [
            'record_id' => $this->record->id,
            'uuid' => $this->record->uuid,
            'correlation_id' => $this->record->correlation_id,
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}