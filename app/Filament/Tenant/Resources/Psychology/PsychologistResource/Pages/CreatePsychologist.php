<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Psychology\PsychologistResource\Pages;

use App\Filament\Tenant\Resources\Psychology\PsychologistResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CreatePsychologist extends CreateRecord
{
    protected static string $resource = PsychologistResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = (string) Str::uuid();
        
        Log::channel('audit')->info('Creating Psychologist via Filament', [
            'data' => $data,
            'correlation_id' => $correlationId,
        ]);

        $data['correlation_id'] = $correlationId;
        $data['tenant_id'] = auth()->user()->tenant_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
