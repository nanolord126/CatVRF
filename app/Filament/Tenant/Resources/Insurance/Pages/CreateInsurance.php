<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Insurance\Pages;

use App\Filament\Tenant\Resources\Insurance\InsuranceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\{Log,DB};
use Illuminate\Support\Str;

final class CreateInsurance extends CreateRecord
{
    protected static string $resource = InsuranceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = Str::uuid()->toString();
        
        DB::transaction(function () use (&$data, $correlationId) {
            $data['correlation_id'] = $correlationId;
            $data['tenant_id'] = filament()->getTenant()->id;
            $data['uuid'] = Str::uuid()->toString();

            Log::channel('audit')->info('Insurance creation form submitted', [
                'correlation_id' => $correlationId,
                'tenant_id' => $data['tenant_id'],
                'user_id' => auth()->id(),
            ]);
        });

        return $data;
    }

    protected function afterCreate(): void
    {
        Log::channel('audit')->info('Insurance record created successfully', [
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